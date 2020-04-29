<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use App\Wordpress\QueryUser;
use Illuminate\Support\Collection;
use tiFy\Contracts\Mail\Mail as MailContract;
use tiFy\Contracts\PostType\PostTypeStatus;
use tiFy\Support\Proxy\Router;
use tiFy\Wordpress\Contracts\Query\QueryComment as QueryCommentContract;
use tiFy\Wordpress\Contracts\Query\QueryPost as QueryPostContract;
use tiFy\Wordpress\Contracts\Query\QueryUser as QueryUserContract;
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;
use tiFy\Support\{DateTime, Proxy\Mail};
use WP_Post, WP_Query;

class QueryOrder extends BaseQueryPost
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    protected static $postType = 'subscription-order';

    /**
     * Instance du client associé à la commande.
     * @var QueryUser|false|null
     */
    protected $customer;

    /**
     * Liste des instances de produits associés à la commande.
     * @var QueryOrderLineItem[]|array|null
     */
    protected $lineItems;

    /**
     * Cartographie des clés d'indice de métadonnées associées à la commande.
     * @var array
     */
    protected $metasMap = [
        'card_last4'            => '_card_last4',
        'created_via'           => '_created_via',
        'currency'              => '_order_currency',
        'customer_id'           => '_customer_user',
        'customer_ip_address'   => '_customer_ip_address',
        'customer_user_agent'   => '_customer_user_agent',
        'date_completed'        => '_date_completed',
        'date_paid'             => '_date_paid',
        'order_key'             => '_order_key',
        'payment_method'        => '_payment_method',
        'payment_method_title'  => '_payment_method_title',
        'prices_include_tax'    => '_prices_include_tax',
        'stripe_payment_intent' => '_stripe_payment_intent',
        'subscription_id'       => '_subscription_id',
        'transaction_id'        => '_transaction_id',
        'total'                 => '_order_total',
        'total_tax'             => '_order_tax',
        'version'               => '_order_version',
    ];

    /**
     * CONSTRUCTEUR.
     *
     * @param WP_Post|null $wp_post Instance de post Wordpress.
     *
     * @return void
     */
    public function __construct(?WP_Post $wp_post = null)
    {
        $this->setSubscription(subscription());

        parent::__construct($wp_post);
    }

    /**
     * Création d'une instance basée sur la clé d'identification de la commande.
     *
     * @param string $orderKey
     *
     * @return static|null
     */
    public static function createFromOrderKey(string $orderKey): ?self
    {
        $wpQuery = new WP_Query(static::parseQueryArgs([
            'meta_key'    => '_order_key',
            'meta_value'  => $orderKey,
            'post_status' => 'any',
        ]));

        if ($wpQuery->found_posts == 1) {
            return static::is($instance = new static(current($wpQuery->posts))) ? $instance : null;
        } else {
            return null;
        }
    }

    /**
     * Création d'une nouvelle commande.
     *
     * @return static|null
     */
    public static function insert(): ?QueryPostContract
    {
        return ($id = wp_insert_post(['post_type' => static::$postType, 'post_status' => 'publish']))
            ? static::createFromId($id) : null;
    }

    /**
     * Ajout d'un message de note de commande.
     *
     * @param string $note Message
     *
     * @return int
     */
    public function addNote(string $note): int
    {
        if (!$this->getId()) {
            return 0;
        }

        $comment_id = wp_insert_comment([
            'comment_post_ID'      => $this->getId(),
            'comment_author'       => 'internal',
            'comment_author_email' => 'internal@noreply.com',
            'comment_author_url'   => '',
            'comment_content'      => $note,
            'comment_agent'        => 'internal',
            'comment_type'         => 'order_note',
            'comment_parent'       => 0,
            'comment_approved'     => 1,
        ]);

        return $comment_id ?: 0;
    }

    /**
     * Calcul du total de la commande basé sur la liste des produits associés.
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        return (new Collection($this->getLineItems()))->sum('total') ?: 0;
    }

    /**
     * Calcul de la taxe total de la commande basée sur la liste des produits associés.
     *
     * @return float
     */
    public function calculateTotalTax(): float
    {
        return (new Collection($this->getLineItems()))->sum('total_tax') ?: 0;
    }

    /**
     * Réinitialisation de la liste des produits associés à la commande.
     *
     * @return static
     */
    public function clearLineItems(): self
    {
        $this->lineItems = null;

        return $this;
    }

    /**
     * Récupération d'attribut d'adresse de facturation.
     *
     * @param string|null $key Clé d'indice
     * @param mixed $default Valeur de retour par défaut
     *
     * @return mixed
     */
    public function getBilling(?string $key = null, $default = null)
    {
        return is_null($key) ? $this->get('billing', []) : $this->get("billing.{$key}", $default);
    }

    /**
     * Récupération des 4 derniers numéro de carte.
     *
     * @return string
     */
    public function getCardLast4(): string
    {
        return (string)$this->get('card_last4', '');
    }

    /**
     * Récupération de l'utilisateur associé à la commande.
     *
     * @return QueryUser
     */
    public function getCustomer(): ?QueryUserContract
    {
        if (is_null($this->customer)) {
            $this->customer = ($id = $this->getCustomerId()) ? QueryUser::createFromId($id) : false;
        }

        return $this->customer ?: null;
    }

    /**
     * Récupération de l'utilisateur associé à la commande.
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int)$this->get('customer_id', 0);
    }

    /**
     * Récupération de l'adresse IP du client.
     *
     * @return string
     */
    public function getCustomerIp(): string
    {
        return (string)$this->get('customer_ip_address', '');
    }

    /**
     * Récupération du navigateur du client.
     *
     * @return string
     */
    public function getCustomerUserAgent(): string
    {
        return (string)$this->get('customer_user_agent', '');
    }

    /**
     * Récupération du mail.
     *
     * @param array $attrs
     *
     * @return MailContract
     */
    public function getMail(array $attrs = []): MailContract
    {
        return Mail::create(array_merge([
            'subject' => sprintf(
                __('[%s] >> Votre commande n°%d', 'theme'), get_bloginfo('blogname'), $this->getId()
            ),
            'to'      => $this->getCustomer()->getEmail(),
            'viewer'  => [
                'override_dir' => get_template_directory() . '/views/mail/order',
            ],
        ], $attrs))->data($this->getInvoiceDatas());
    }

    /**
     * Récupération des données de facture.
     *
     * @return array
     */
    public function getInvoiceDatas(): array
    {
        $cinfos = get_option('contact_infos');
        $user = $this->getCustomer();

        return [
            'billing'  => [
                'display_name' => "{$this->getBilling('lastname')} {$this->getBilling('firstname')}",
                'company'      => $this->getBilling('company'),
                'address1'     => $this->getBilling('address1'),
                'address2'     => $this->getBilling('address2'),
                'postcode'     => $this->getBilling('postcode'),
                'city'         => $this->getBilling('city'),
                'email'        => $this->getBilling('email'),
                'phone'        => $this->getBilling('phone'),
            ],
            'company'      => [
                'name'  => $cinfos['company_name'] ?? '',
                'form'  => $cinfos['company_form'] ?? '',
                'siren' => $cinfos['company_siren'] ?? '',
                'siret' => $cinfos['company_siret'] ?? '',
                'tva'   => $cinfos['company_tva'] ?? '',
                'ape'   => $cinfos['company_ape'] ?? '',
                'cnil'  => $cinfos['company_cnil'] ?? '',
            ],
            'contact'      => [
                'address1' => $cinfos['contact_address1'] ?? '',
                'address2' => $cinfos['contact_address2'] ?? '',
                'postcode' => $cinfos['contact_postcode'] ?? '',
                'city'     => $cinfos['contact_city'] ?? '',
                'phone'    => $cinfos['contact_phone'] ?? '',
                'fax'      => $cinfos['contact_fax'] ?? '',
                'email'    => $cinfos['contact_email'] ?? '',
                'website'  => $cinfos['contact_website'] ?? '',
            ],
            'date'     => [
                'created' => $this->getDateTime()->format('d/m/Y'),
                'payment' => $this->getPaymentDatetime()->format('d/m/Y'),
            ],
            'logo'     => '', //$this->app->img()->src('svg/logo-mono.svg'),
            'items'    => $this->getLineItems(),
            'order'    => [
                'id'                => $this->getId(),
                'payment_method'    => $this->getPaymentMethodTitle(),
                'tax'               => $this->getTotalTax(),
                'total'             => $this->getTotalWithTax(),
                'total_without_tax' => $this->getTotalWithoutTax(),
            ],
            'shipping' => [
                'display_name' => "{$this->getShipping('lastname')} {$this->getShipping('firstname')}",
                'address1'     => $this->getShipping('address1'),
                'address2'     => $this->getShipping('address2'),
                'postcode'     => $this->getShipping('postcode'),
                'city'         => $this->getShipping('city'),
            ],
            'subscription'     => [
                'id'        => $this->getSubscriptionId()
            ],
            'url'      => [
                'order'        => $this->getUrl(true),
                'pdf'          => $this->getPdfUrl(true),
                'pdf-download' => $this->getPdfDownloadUrl(true),
            ],
            'user'     => [
                'display_name' => $user->getDisplayName(),
                'email'        => $user->getEmail(),
            ],
        ];
    }

    /**
     * Récupération de la liste des produits associés à la commande.
     *
     * @return QueryOrderLineItem[]|array
     */
    public function getLineItems(): array
    {
        if (is_null($this->lineItems)) {
            $this->lineItems = QueryOrderLineItem::fetchFromOrder($this);
        }

        return $this->lineItems;
    }

    /**
     * Récupération de métadonnée cartographiée.
     *
     * @param string|array|null $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getMetaMapped($key = null, $default = null)
    {
        $metaMapKeys = array_keys($this->metasMap);
        array_push($metaMapKeys, 'billing', 'shipping');

        if (is_null($key)) {
            return $this->only($metaMapKeys);
        } elseif (is_array($key)) {
            $keys = array_intersect($key, $metaMapKeys);

            return $this->only($keys);
        } elseif (is_string($key) && in_array($key, $metaMapKeys)) {
            return $this->get($key, $default);
        }

        return $default;
    }

    /**
     * Récupération de la date de paiement.
     *
     * @return DateTime|null
     */
    public function getPaymentDatetime(): ?DateTime
    {
        if ($this->isStatus('completed') && ($time = (int)$this->get('date_completed', 0))) {
            return Datetime::createFromTimestamp($time, Datetime::getGlobalTimeZone());
        } elseif ($time = (int)$this->get('date_paid', 0)) {
            return Datetime::createFromTimestamp($time, Datetime::getGlobalTimeZone());
        } else {
            return null;
        }
    }

    /**
     * Récupération de l'intitulé de qualification du moyen de paiement.
     *
     * @return string
     */
    public function getPaymentMethodTitle(): string
    {
        return (string)$this->get('payment_method_title', '');
    }

    /**
     * Récupération de l'url de téléchargement du PDF.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getPdfDownloadUrl(bool $absolute = false): string
    {
        return Router::url('account.order.invoice.pdf-download', [$this->getId()], $absolute);
    }

    /**
     * Récupération de l'url vers le PDF.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getPdfUrl(bool $absolute = false): string
    {
        return Router::url('account.order.invoice.pdf', [$this->getId()], $absolute);
    }

    /**
     * Récupération des notes de commande.
     *
     * @return QueryCommentContract[]|array
     */
    public function getNotes(): array
    {
        return $this->getComments(['type' => 'order_note', 'orderby' => 'comment_ID']);
    }

    /**
     * Récupération de la clé de qualification de commande.
     *
     * @return string|null
     */
    public function getOrderKey(): ?string
    {
        return $this->get('order_key');
    }

    /**
     * Récupération d'attribut d'adresse de livraison.
     *
     * @param string|null $key Clé d'indice
     * @param mixed $default Valeur de retour par défaut
     *
     * @return mixed
     */
    public function getShipping(?string $key = null, $default = null)
    {
        return is_null($key) ? $this->get('shipping', []) : $this->get("shipping.{$key}", $default);
    }

    /**
     * {@inheritDoc}
     *
     * @return OrderStatus|PostTypeStatus
     */
    public function getStatus(): PostTypeStatus
    {
        return $this->subscription()->order()->status($this->get('post_status', '')) ?: parent::getStatus();
    }

    /**
     * Récupération de l'identifiant de l'intention de paiement Stripe.
     *
     * @return string
     */
    public function getStripePaymentIntentId(): string
    {
        return $this->get('stripe_payment_intent', '') ?: '';
    }

    /**
     * Récupération de l'identifiant de qualification de l'abonnement associé.
     *
     * @return int
     */
    public function getSubscriptionId(): int
    {
        return (int)$this->get('subscription_id', 0);
    }

    /**
     * Récupération de l'identifiant de transaction.
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->get('transaction_id', '') ?: '';
    }

    /**
     * Récupération de la valeur du total.
     *
     * @return float
     */
    public function getTotal(): float
    {
        return (float)$this->get('total', $this->calculateTotal());
    }

    /**
     * Récupération du total affiché au format HTML.
     *
     * @param bool $with_tax
     *
     * @return string
     */
    public function getTotalHtml(bool $with_tax = true): string
    {
        return $this->subscription()->functions()->displayPrice(
            $with_tax ? $this->getTotalWithTax() : $this->getTotalWithoutTax()
        );
    }

    /**
     * Récupération de la valeur de la taxe totale.
     *
     * @return float
     */
    public function getTotalTax()
    {
        return (float)$this->get('total_tax', $this->calculateTotalTax());
    }

    /**
     * Récupération de la taxe totale affichée au format HTML.
     *
     * @return string
     */
    public function getTotalTaxHtml(): string
    {
        return $this->subscription()->functions()->displayPrice($this->getTotalTax());
    }

    /**
     * Récupération du total incluant la taxe.
     *
     * @return float
     */
    public function getTotalWithTax(): float
    {
        $total = $this->getTotal();

        if ($this->isTotalTaxable() && !$this->isPricesIncludeTax()) {
            $tax = $this->getTotalTax();
            $total += $tax;
        }

        return round($total, $this->subscription()->settings()->getPriceDecimals());
    }

    /**
     * Récupération du total hors taxe.
     *
     * @return float
     */
    public function getTotalWithoutTax(): float
    {
        $total = $this->getTotal();

        if ($this->isTotalTaxable() && $this->isPricesIncludeTax()) {
            $tax = $this->getTotalTax();
            $total -= $tax;
        }

        return round($total, $this->subscription()->settings()->getPriceDecimals());
    }

    /**
     * Récupération de l'url d'affichage de la commande.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function getUrl(bool $absolute = false): string
    {
        return Router::url('account.order', [$this->getId()], $absolute);
    }

    /**
     * Vérifie si le calcul des prix inclus la taxe.
     *
     * @return bool
     */
    public function isPricesIncludeTax(): bool
    {
        return !!$this->get('prices_include_tax');
    }

    /**
     * Vérifie si le statut courant correspond à un statut passé en argument.
     *
     * @param string $alias_or_name Alias ou nom de qualification du statut à vérifier.
     *
     * @return bool
     */
    public function isStatus(string $alias_or_name): bool
    {
        if (($status = $this->getStatus()) instanceof OrderStatus) {
            return $this->getStatus()->getName() === $alias_or_name || $this->getStatus()->getAlias() === $alias_or_name;
        } else {
            return $this->getStatus()->getName() === $alias_or_name;
        }
    }

    /**
     * Vérifie si le statut courant requiert un paiement.
     *
     * @return bool
     */
    public function isStatusNeedPayment(): bool
    {
        return $this->subscription()->order()->statusNeedPaid($this->getStatus()->getName());
    }

    /**
     * Vérifie si un statut courant indique un paiement complet.
     *
     * @return bool
     */
    public function isStatusPaymentComplete(): bool
    {
        return $this->subscription()->order()->statusPaymentCompleted($this->getStatus()->getName());
    }

    /**
     * Vérifie si le taxe est active pour la commande.
     *
     * @return bool
     */
    public function isTotalTaxable(): bool
    {
        return $this->subscription()->settings()->isTaxEnabled() && !!$this->getTotalTax();
    }

    /**
     * Cartographie de métadonnées de commande.
     *
     * @param string|array|null Clé d'indice ou table de définition. Si null cartographie l'ensemble des métadonnées.
     * @param string|null Clé d'indice de la métadonnées associée.
     *
     * @return static
     */
    public function mapMeta($key = null, ?string $metaKey = null): self
    {
        if (is_null($key)) {
            $keys = $this->metasMap;
        } else {
            $keys = is_array($key) ? $key : [$key => $metaKey];
        }

        foreach ($keys as $key => $metaKey) {
            if (is_string($metaKey)) {
                $this->set($key, $this->getMetaSingle($metaKey));
            }
        }

        return $this;
    }

    /**
     * Cartographie de métadonnées d'adresse de facturation de commande.
     *
     * @return static
     */
    public function mapBillingMeta(): self
    {
        $keys = [
            'lastname',
            'firstname',
            'company',
            'address1',
            'address2',
            'postcode',
            'city',
            'phone',
            'email',
            'same',
        ];
        foreach ($keys as $key) {
            $this->mapMeta("billing.{$key}", "_billing_{$key}");
        }

        return $this;
    }

    /**
     * Cartographie de métadonnées d'adresse de livraison de commande.
     *
     * @return static
     */
    public function mapShippingMeta(): self
    {
        $keys = [
            'lastname',
            'firstname',
            'company',
            'address1',
            'address2',
            'postcode',
            'city',
        ];
        foreach ($keys as $key) {
            $this->mapMeta("shipping.{$key}", "_shipping_{$key}");
        }

        return $this;
    }

    /**
     * Traitement de la liste des variables de commande.
     *
     * @return static
     */
    public function parse(): self
    {
        parent::parse();

        if (!$id = $this->getId()) {
            return $this;
        }

        $this->mapMeta()->mapBillingMeta()->mapShippingMeta();

        $this->set([
            'date_created'  => $this->getDate(true),
            'date_modified' => $this->getModified(true),
            'line_items'    => $this->getMetaSingle('_line_items', []),
            'order_key'     => $this->getOrderKey() ?: uniqid('order_'),
            'status'        => ($s = $this->getStatus())
                ? $s->getName() : ($this->subscription()->order()->statusDefault()->getName() ?: 'order-pending'),
        ]);

        return $this;
    }

    /**
     * Définition d'une instance de ligne de produit associée à la commande.
     *
     * @param QueryOrderLineItem $lineItem
     *
     * @return static
     */
    public function setLineItem(QueryOrderLineItem $lineItem): self
    {
        $this->lineItems[$lineItem->getHash() ?: $lineItem->generateHash()] = $lineItem;

        return $this;
    }

    /**
     * Mise à jour des données de commande en base de donnée.
     *
     * @return void
     */
    public function update(): void
    {
        $postdata = [
            'ID'          => $this->getId(),
            'post_status' => ($status = $this->subscription()->order()->status($this->get('status')))
                ? $status->getName() : ($this->subscription()->order()->statusDefault()->getName() ?: 'order-pending'),
            'meta'        => [],
        ];

        $postdata['post_title'] = sprintf(__('Commande n°%s', 'theme'), $this->getId());
        if ($date = $this->getDate()) {
            $postdata['post_title'] .= ' &ndash; ' . date_i18n('j F Y @ H:i A', strtotime($date));
        }

        foreach ($this->metasMap as $key => $metaKey) {
            $postdata['meta'][$metaKey] = $this->get($key);
        }

        foreach (['billing', 'shipping'] as $type) {
            if ($data = $this->get($type, [])) {
                foreach ($data as $key => $value) {
                    $postdata['meta']["_{$type}_{$key}"] = $value;
                }
                $postdata['meta']["_{$type}_address_index"] = implode(' ', $data);
            }
        }

        $postdata['meta']['_line_items'] = [];
        foreach ($this->getlineItems() as $lineItem) {
            $postdata['meta']['_line_items'][$lineItem->getHash() ?: $lineItem->generateHash()] = $lineItem->all();
        }

        $this->save($postdata);
    }

    /**
     * Mise à jour du statut.
     *
     * @param string $new_status
     *
     * @return bool
     */
    public function updateStatus(string $new_status): bool
    {
        if (!$statusObj = $this->subscription()->order()->status($new_status)) {
            return false;
        } elseif ($this->isStatus($new_status)) {
            return false;
        } else {
            $this->set('status', $new_status);

            if (!$this->get('date_paid') && $this->subscription()->order()->statusPaymentCompleted($new_status)) {
                $this->set('date_paid', (new DateTime())->utc('U'));
            }

            if (!$this->get('date_completed') && $statusObj->getName() === 'order-completed') {
                $this->set('date_completed', (new DateTime())->utc('U'));
            }

            $this->update();

            return true;
        }
    }
}