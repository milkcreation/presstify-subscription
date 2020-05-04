<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use Illuminate\Support\Collection;
use tiFy\Contracts\Mail\Mail as MailContract;
use tiFy\Contracts\PostType\PostTypeStatus;
use tiFy\Plugins\Subscription\{
    Contracts\PaymentGateway,
    SubscriptionAwareTrait
};
use tiFy\Plugins\Subscription\{QuerySubscription, SubscriptionCustomer};
use tiFy\Wordpress\Contracts\Query\{
    QueryComment as QueryCommentContract,
    QueryPost as QueryPostContract,
};
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;
use tiFy\Support\DateTime;
use WP_Post, WP_Query;

class QueryOrder extends BaseQueryPost
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    protected static $postType = 'subscription-order';

    /**
     * Cartographie des clés d'indice de métadonnées associées à la commande.
     * @var string[]
     */
    protected static $metasMap = [
        'card_first'           => '_card_first',
        'card_last'            => '_card_last',
        'card_valid'           => '_card_valid',
        'created_via'          => '_created_via',
        'currency'             => '_order_currency',
        'customer_email'       => '_customer_email',
        'customer_id'          => '_customer_user',
        'customer_ip_address'  => '_customer_ip_address',
        'customer_user_agent'  => '_customer_user_agent',
        'date_completed'       => '_date_completed',
        'date_paid'            => '_date_paid',
        'misc'                 => '_misc',
        'order_key'            => '_order_key',
        'payment_method'       => '_payment_method',
        'payment_method_title' => '_payment_method_title',
        'prices_include_tax'   => '_prices_include_tax',
        'subscription_id'      => '_subscription_id',
        'subscription_form'    => '_subscription_form',
        'transaction_id'       => '_transaction_id',
        'total'                => '_order_total',
        'total_tax'            => '_order_tax',
        'version'              => '_order_version',
    ];

    /**
     * Instance du client associé à la commande.
     * @var SubscriptionCustomer|null
     */
    protected $customer;

    /**
     * Liste des instances de produits associés à la commande.
     * @var QueryOrderLineItem[]|array|null
     */
    protected $lineItems;

    /**
     * Instance de la plateforme de paiement associée.
     * @var PaymentGateway|false|null
     */
    protected $paymentGateway;

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
     * @inheritDoc
     */
    public static function create($id = null, ...$args): ?QueryPostContract
    {
        if (is_numeric($id)) {
            return static::createFromId((int)$id);
        } elseif (is_string($id)) {
            return static::createFromOrderKey($id);
        } elseif ($id instanceof WP_Post) {
            return static::build($id);
        } elseif ($id instanceof QueryPostContract) {
            return static::createFromId($id->getId());
        } elseif (is_null($id)) {
            return static::createFromGlobal();
        } else {
            return null;
        }
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
        return ($id = wp_insert_post([
            'post_type'   => static::$postType,
            'post_status' => subscription()->order()->statusDefault()->getName(),
        ])) ? static::createFromId($id) : null;
    }

    /**
     * Définition de metadonnées complémentaires.
     *
     * @param string[] $map
     *
     * @return void
     */
    public static function setMetasMap(array $map): void
    {
        static::$metasMap = array_merge($map, static::$metasMap);
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
     * Création d'une instance de ligne de produit associée à la commande.
     *
     * @param array $attrs
     *
     * @return QueryOrderLineItem
     */
    public function createLineItem(array $attrs = []): QueryOrderLineItem
    {
        $lineItem = new QueryOrderLineItem($attrs);

        return $this->lineItems[$lineItem->getHash() ?: $lineItem->generateHash()] = $lineItem;
    }

    /**
     * Récupération de l'instance de la plateforme de paiement associée.
     *
     * @return PaymentGateway|null
     */
    public function getPaymentGateway(): ?PaymentGateway
    {
        if (is_null($this->paymentGateway)) {
            if ($paymentGateway = $this->subscription()->gateway()->get($this->get('payment_method'))) {
                $this->paymentGateway = $paymentGateway->setOrder($this);
            } else {
                $this->paymentGateway = false;
            }
        }

        return $this->paymentGateway ?: null;
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
     * Récupération des premiers numéros de la carte bancaire.
     *
     * @return string
     */
    public function getCardFirst(): string
    {
        return (string)$this->get('card_first', '');
    }

    /**
     * Récupération des derniers numéros de la carte bancaire.
     *
     * @return string
     */
    public function getCardLast(): string
    {
        return (string)$this->get('card_last', '');
    }

    /**
     * Récupération de la date de validité de la carte bancaire.
     *
     * @return string
     */
    public function getCardValid(): string
    {
        return (string)$this->get('card_valid', '');
    }

    /**
     * Récupération de l'utilisateur associé à la commande.
     *
     * @return SubscriptionCustomer
     */
    public function getCustomer(): ?SubscriptionCustomer
    {
        if (is_null($this->customer)) {
            $this->customer = ($id = $this->getCustomerId())
                ? $this->subscription()->customer($id) : $this->subscription()->customer($this->getCustomerEmail());
        }

        return $this->customer ?: null;
    }

    /**
     * Récupération de l'utilisateur associé à la commande.
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return (string)$this->get('customer_email') ?: ($this->getBilling('email') ?: '');
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
     * Récupération de l'url de traitement de l'annulation de paiement.
     *
     * @return string
     */
    public function getHandleCancelledUrl(): string
    {
        return $this->subscription()->route('handle-cancelled')->getUrl([$this->getOrderKey()], true);
    }

    /**
     * Récupération de l'url de traitement de l'échec de paiement.
     *
     * @return string
     */
    public function getHandleFailedUrl(): string
    {
        return $this->subscription()->route('handle-failed')->getUrl([$this->getOrderKey()], true);
    }

    /**
     * Récupération de l'url de traitement de la notification de paiement instantané.
     *
     * @return string
     */
    public function getHandleIpnUrl(): string
    {
        return $this->subscription()->route('handle-ipn')->getUrl([$this->getOrderKey()], true);
    }

    /**
     * Récupération de l'url de traitement d'un paiement en attente de réglement.
     *
     * @return string
     */
    public function getHandleOnHoldUrl(): string
    {
        return $this->subscription()->route('handle-on-hold')->getUrl([$this->getOrderKey()], true);
    }

    /**
     * Récupération de l'url de traitement de succès de paiement.
     *
     * @return string
     */
    public function getHandleSuccessedUrl(): string
    {
        return $this->subscription()->route('handle-successed')->getUrl([$this->getOrderKey()], true);
    }

    /**
     * Récupération du mail.
     *
     * @param array $params
     *
     * @return MailContract
     */
    public function getMail(array $params = []): MailContract
    {
        return $this->subscription()->mail()->order($this)->setParams($params);
    }

    /**
     * Récupération des données de facture.
     *
     * @return array
     */
    public function getInvoiceDatas(): array
    {
        return [
            'billing'      => [
                'display_name' => "{$this->getBilling('firstname')} {$this->getBilling('lastname')} ",
                'company'      => $this->getBilling('company'),
                'address1'     => $this->getBilling('address1'),
                'address2'     => $this->getBilling('address2'),
                'postcode'     => $this->getBilling('postcode'),
                'city'         => $this->getBilling('city'),
                'email'        => $this->getBilling('email'),
                'phone'        => $this->getBilling('phone'),
            ],
            'items'        => $this->getLineItems(),
            'order'        => [
                'id'                => $this->getId(),
                'created_date'      => $this->getDateTime()->format('d/m/Y'),
                'payment_date'      => $this->getPaymentDatetime()->format('d/m/Y'),
                'payment_method'    => $this->getPaymentMethodTitle(),
                'tax'               => $this->subscription()->functions()->displayPrice($this->getTotalTax()),
                'taxable'           => $this->isTotalTaxable(),
                'total'             => $this->subscription()->functions()->displayPrice($this->getTotalWithTax()),
                'total_without_tax' => $this->subscription()->functions()->displayPrice($this->getTotalWithoutTax()),
            ],
            'shipping'     => [
                'display_name' => "{$this->getShipping('lastname')} {$this->getShipping('firstname')}",
                'address1'     => $this->getShipping('address1'),
                'address2'     => $this->getShipping('address2'),
                'postcode'     => $this->getShipping('postcode'),
                'city'         => $this->getShipping('city'),
            ],
            'subscription' => [
                'id'    => $this->getSubscriptionId(),
                'label' => $this->getSubscriptionLabel(),
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
        $metaMapKeys = array_keys(static::$metasMap);
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
     * Récupération de l'instance de l'abonnement associé.
     *
     * @return QuerySubscription|null
     */
    public function getSubscription(): ?QuerySubscription
    {
        return $this->subscription()->get($this->getSubscriptionId());
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
     * Récupération de l'intitulé de qualification de l'abonnement associé.
     *
     * @return string
     */
    public function getSubscriptionLabel(): string
    {
        return (string)$this->get('subscription_label', ($s = $this->getSubscription()) ? $s->getLabel() : '');
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
     * Vérifie si une commande nécessite une livraison.
     *
     * @return bool
     */
    public function isNeedShipping(): bool
    {
        return false;
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
            $keys = static::$metasMap;
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
                ? $s->getName() : ($this->subscription()->order()->statusDefault()->getName() ?: 'sbscodr-pending'),
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
            'ID'                => $this->getId(),
            'post_status'       => ($status = $this->subscription()->order()->status($this->get('status')))
                ? $status->getName() : ($this->subscription()->order()->statusDefault()->getName() ?: 'sbscodr-pending'),
            'meta'              => [],
            'post_modified'     => DateTime::now(DateTime::getGlobalTimeZone())->toDateTimeString(),
            'post_modified_gmt' => DateTime::now('gmt')->toDateTimeString(),
        ];

        $postdata['post_title'] = sprintf(__('Commande n°%s', 'tify'), $this->getId());
        if ($date = $this->getDate()) {
            $postdata['post_title'] .= ' &ndash; ' . date_i18n('j F Y @ H:i A', strtotime($date));
        }

        foreach (static::$metasMap as $key => $metaKey) {
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
     * @param string $new_status pending|processing|on-hold|completed|cancelled|refunded|failed
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

            if (!$this->get('date_completed') && $statusObj->getName() === 'sbscodr-completed') {
                $this->set('date_completed', (new DateTime())->utc('U'));
            }

            $this->update();

            return true;
        }
    }
}