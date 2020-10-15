<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use Illuminate\Support\Collection;
use tiFy\Contracts\Mail\Mail as MailContract;
use tiFy\Plugins\Subscription\{Offer\QueryOffer, Order\QueryOrder};
use tiFy\Support\DateTime;
use tiFy\Support\Proxy\Crypt;
use tiFy\Wordpress\Contracts\Query\QueryPost as QueryPostContract;
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;
use WP_Post;

class QuerySubscription extends BaseQueryPost
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    protected static $postType = 'subscription';

    /**
     * Cartographie des métadonnées associées à l'abonnement.
     * @var string[]
     */
    protected static $metasMap = [
        'customer_email'        => '_customer_email',
        'customer_id'           => '_customer_id',
        'customer_display_name' => '_customer_display_name',
        'end_date'              => '_end_date',
        'imported'              => '_imported',
        'offer_id'              => '_offer_id',
        'offer_label'           => '_offer_label',
        'order_id'              => '_order_id',
        'limited'               => '_limited',
        'limited_length'        => '_limited_length',
        'limited_unity'         => '_limited_unity',
        'renewable'             => '_renewable',
        'renew_days'            => '_renew_days',
        'renew_notify'          => '_renew_notify',
        'renew_notify_days'     => '_renew_notify_days',
        'start_date'            => '_start_date',
        'subscription_number'   => '_subscription_number',
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
     * Récupération d'un abonnement associé à un jeton de renouvellement.
     *
     * @param $token
     *
     * @return static|null
     */
    public static function createFromRenewToken($token): ?self
    {
        if (!$datas = Crypt::decrypt($token)) {
            return null;
        }

        $datas = json_decode($datas, true);

        if (!($id = $datas['id'] ?? 0) && !($email = $datas['email'] ?? null)) {
            return null;
        }

        /** @var self $obj */
        $obj = static::createFromId($id);
        if(!($obj instanceof static) && ($obj->getCustomerEmail() !== $email) && !$obj->isRenewable()) {
            return null;
        }

        return $obj;
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
     * Génération du nom de qualification.
     *
     * @return string
     */
    public function generateTitle(): string
    {
        $title = ($num = $this->getNumber())
            ? sprintf(__('%s - %s', 'tify'), $this->getType()->label('singular_name'), $num)
            : sprintf(__('%s - #%d', 'tify'), $this->getType()->label('singular_name'), $this->getId());

        if ($date = $this->getDate()) {
            $title .= ' &ndash; ' . date_i18n('j F Y @ H:i A', strtotime($date));
        }

        return $title;
    }

    /**
     * Récupération du client associé.
     *
     * @return SubscriptionCustomer|null
     */
    public function getCustomer(): ?SubscriptionCustomer
    {
        return ($id = (int)$this->get('customer_id'))
            ? $this->subscription()->customer($id)
            : $this->subscription()->customer($this->get('customer_email'))->set([
                'display_name' => $this->getCustomerDisplayName()
            ]);
    }

    /**
     * Récupération de l'email de contact du client associé.
     *
     * @return string
     */
    public function getCustomerDisplayName(): string
    {
        return $this->get('customer_display_name') ?: '';
    }

    /**
     * Récupération de l'email de contact du client associé.
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->get('customer_email') ?: '';
    }

    /**
     * Récupération de la date de fin.
     *
     * @return DateTime|null
     */
    public function getEndDate(): ?DateTime
    {
        return ($date = $this->get('end_date')) ? DateTime::createFromTimeString($date) : null;
    }

    /**
     * Récupération de l'intitulé de qualification.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return (string)$this->get('offer_label', '');
    }

    /**
     * Récupération de la durée de l'abonnement au format HTML.
     *
     * @return string|null
     */
    public function getLimitedHtml(): ?string
    {
        if (!$length = $this->getLimitedLength()) {
            return null;
        }

        switch ($this->getLimitedUnity()) {
            default :
            case 'year' :
                return sprintf(_n('%d an', '%d ans', $length, 'tify'), $length);
                break;
            case 'month' :
                return sprintf(_n('%d mois', '%d mois', $length, 'tify'), $length);
                break;
            case 'day' :
                return sprintf(_n('%d jour', '%d jours', $length, 'tify'), $length);
                break;
        }
    }

    /**
     * Récupération de la durée de l'abonnement.
     *
     * @return int
     */
    public function getLimitedLength(): int
    {
        return (int)$this->get('limited_length', 0);
    }

    /**
     * Récupération de l'unité de durée de l'abonnement.
     *
     * @return string
     */
    public function getLimitedUnity(): string
    {
        return (string)$this->get('limited_unity') ?: 'year';
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
     * Récupération du numéro d'abonnement.
     *
     * @return string
     */
    public function getNumber(): string
    {
        return (string)$this->get('subscription_number', '');
    }

    /**
     * Récupération de l'offre associée.
     *
     * @return QueryOffer|null
     */
    public function getOffer(): ?QueryOffer
    {
        return ($id = (int)$this->get('offer_id')) ? $this->subscription()->offer()->get($id) : null;
    }

    /**
     * Récupération de la commande.
     *
     * @return QueryOrder|null
     */
    public function getOrder(): ?QueryOrder
    {
        return ($id = (int)$this->get('order_id')) ? $this->subscription()->order()->get($id) : null;
    }

    /**
     * Récupération du nombre de jours de la période de ré-engagement.
     *
     * @return int
     */
    public function getRenewDays(): int
    {
        return (int)$this->get('renew_days', 0) ?: 0;
    }

    /**
     * Récupération du jeton de renouvellement.
     *
     * @return DateTime|null
     */
    public function getRenewToken(): string
    {
        return Crypt::encrypt(json_encode(['id' =>$this->getId(), 'email' => $this->getCustomerEmail()]));
    }

    /**
     * Récupération de l'url de renouvellement.
     *
     * @param array $params
     *
     * @return string
     */
    public function getRenewUrl(array $params = []): string
    {
        return $this->subscription()->route('order-form')->getUrl(
            array_merge($params, ['renew_token' => $this->getRenewToken()]), true
        );
    }

    /**
     * Récupération de la date de ré-engagement.
     *
     * @return DateTime|null
     */
    public function getRenewableDate(): ?DateTime
    {
        if (!$date = $this->getEndDate()) {
            return null;
        }

        $date->subDays($this->getRenewDays());

        return $date;
    }

    /**
     * Date d'éxpédion du rappel de notification de ré-engagement.
     *
     * @return DateTime|null
     */
    public function getRenewNotified(): ?DateTime
    {
        if ($date = $this->getMetaSingle('_renew_notified')) {
            return DateTime::createFromTimeString($date);
        }

        return null;
    }

    /**
     * Récupération du nombre de jours avant l'expiration pour l'expédition du mail de rappel.
     *
     * @return DateTime|null
     */
    public function getRenewNotifyDate(): ?DateTime
    {
        if (!$end = $this->getEndDate()) {
            return null;
        } elseif (!$days = $this->getRenewNotifyDays()) {
            return null;
        }

        return $end->addDay()->subDays($days)->setTime(0, 0, 0);
    }

    /**
     * Récupération du nombre de jours avant l'expiration pour l'expédition du mail de rappel.
     *
     * @return int
     */
    public function getRenewNotifyDays(): int
    {
        return (int)($this->get('renew_notify_days', 0) ?: $this->getRenewDays() / 2);
    }

    /**
     * Récupération de la date de début.
     *
     * @return DateTime|null
     */
    public function getStartDate(): ?DateTime
    {
        return ($date = $this->get('start_date')) ? DateTime::createFromTimeString($date) : null;
    }

    /**
     * Vérifie si l'abonnement a été renouvelé par un autre abonnement.
     *
     * @return bool
     */
    public function hasRenewed(): bool
    {
        if (!$end = $this->getEndDate()) {
            return false;
        }

        $end->setTime(0, 0, 0);

        $subcriptions = $this->getCustomer()->getSubscriptions();

        return !!(new Collection($subcriptions))->first(function (QuerySubscription $item) use ($end) {
            if ($item->getId() === $this->getId()) {
                return false;
            } elseif (!$start = $item->getStartDate()) {
                return false;
            }

            return $start->greaterThanOrEqualTo($end);
        });
    }

    /**
     * Création d'un nouvel abonnement.
     *
     * @return static|null
     */
    public static function insert(): ?QueryPostContract
    {
        return ($id = wp_insert_post(['post_type' => static::$postType])) ? static::createFromId($id) : null;
    }

    /**
     * Vérifie si l'abonnement est dans sa période de validité.
     *
     * @param DateTime|null $date
     *
     * @return bool
     */
    public function isActive(?DateTime $date = null): bool
    {
        if (!$end = $this->getEndDate()) {
            return false;
        }

        if (is_null($date)) {
            $date = DateTime::now(DateTime::getGlobalTimeZone());
        }

        return $end->greaterThan($date);
    }

    /**
     * Vérifie si l'abonnement est arrivé à expiration.
     *
     * @param DateTime|null $date
     *
     * @return bool
     */
    public function isExpired(?DateTime $date = null): bool
    {
        if (!$end = $this->getEndDate()) {
            return true;
        }

        if (is_null($date)) {
            $date = DateTime::now(DateTime::getGlobalTimeZone());
        }

        return $date->greaterThanOrEqualTo($end);
    }

    /**
     * Vérification de l'activation de l'engagement.
     *
     * @return bool
     */
    public function isLimitedEnabled(): bool
    {
        if ($limited = $this->get('limited')) {
            return filter_var($limited, FILTER_VALIDATE_BOOLEAN);
        } else {
            return $this->subscription()->settings()->isOfferLimitedEnabled();
        }
    }

    /**
     * Vérification de l'activation du ré-engagement.
     *
     * @return bool
     */
    public function isRenewEnabled(): bool
    {
        if ($renewable = $this->get('renewable')) {
            return filter_var($renewable, FILTER_VALIDATE_BOOLEAN);
        } else {
            return $this->subscription()->settings()->isOfferRenewEnabled();
        }
    }

    /**
     * Vérification de l'envoi d'un mail de notification lors de la période de ré-engagement.
     *
     * @return bool
     */
    public function isRenewNotifyEnabled(): bool
    {
        return filter_var($this->get('renew_notify'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si l'abonnement est dans sa période de ré-engagement.
     *
     * @param DateTime|null $date
     *
     * @return bool
     */
    public function isRenewable(?DateTime $date = null): bool
    {
        if (!$end = $this->getEndDate()) {
            return false;
        }

        $end->subDays($this->getRenewDays());

        if (is_null($date)) {
            $date = DateTime::now(DateTime::getGlobalTimeZone());
        }

        return $date->greaterThan($end);
    }

    /**
     * Cartographie de métadonnées de l'abonnement.
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
     * Vérifie si le rappel d'abonnement doit être expédié.
     *
     * @param DateTime|null $date
     *
     * @return bool
     */
    public function mustRenewNotify(?DateTime $date = null): bool
    {
        if (!$this->isRenewEnabled()) {
            return false;
        } elseif (!$this->isRenewNotifyEnabled()) {
            return false;
        } elseif ($this->getRenewNotified()) {
            return false;
        } elseif (!$notify = $this->getRenewNotifyDate()) {
            return false;
        }

        if (is_null($date)) {
            $date = DateTime::now(DateTime::getGlobalTimeZone());
        }

        if ($this->isExpired()) {
            return false;
        }

        return $date->greaterThanOrEqualTo($notify);
    }

    /**
     * Traitement de la liste des variables de l'abonnement
     *
     * @return static
     */
    public function parse(): self
    {
        parent::parse();

        if (!$id = $this->getId()) {
            return $this;
        }

        return $this->mapMeta();
    }

    /**
     * Email de notification de renouvellement d'abonnement en expiration.
     *
     * @param array $params
     *
     * @return MailContract|null
     */
    public function renewNotifyMail(array $params = []): ?MailContract
    {
        return $this->subscription()->mail()->renewNotify($this)->setParams($params);
    }

    /**
     * Mise à jour des données de l'abonnement en base de donnée.
     *
     * @return void
     */
    public function update(): void
    {
        $postdata = [
            'ID'          => $this->getId(),
            'post_status' => 'publish',
            'meta'        => [],
        ];

        $postdata['post_title'] = $this->generateTitle();

        foreach (static::$metasMap as $key => $metaKey) {
            $postdata['meta'][$metaKey] = $this->get($key);
        }

        $this->save($postdata);
    }
}