<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Plugins\Subscription\Offer\QueryOffer;
use tiFy\Wordpress\Contracts\Query\QueryPost as QueryPostContract;
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;
use tiFy\Support\DateTime;
use WP_Post;

class QuerySubscription extends BaseQueryPost
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    protected static $postType = 'subscription';

    /**
     * Cartographie des clés d'indice de métadonnées associées à la commande.
     * @var array
     */
    protected $metasMap = [
        'customer_email'     => '_customer_email',
        'customer_id'        => '_customer_id',
        'duration_length'    => '_duration_length',
        'duration_unity'     => '_duration_unity',
        'end_date'           => '_end_date',
        'imported'           => '_imported',
        'offer_id'           => '_offer_id',
        'offer_label'        => '_offer_label',
        'order_id'           => '_order_id',
        'limited'            => '_limited',
        'renewable'          => '_renewable',
        'renewable_days'     => '_renewable_days',
        'renew_notification' => '_renew_notification',
        'start_date'         => '_start_date',
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
     * Récupération de l'email de contact du client associé.
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        return $this->get('customer_email') ?: '';
    }

    /**
     * Récupération de la durée de l'abonnement au format HTML.
     *
     * @return string|null
     */
    public function getDurationHtml(): ?string
    {
        if (!$length = $this->getDurationLength()) {
            return null;
        }

        switch ($this->getDurationUnity()) {
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
    public function getDurationLength(): int
    {
        return (int)$this->get('duration_length', 0);
    }

    /**
     * Récupération de l'unité de durée de l'abonnement.
     *
     * @return string
     */
    public function getDurationUnity(): string
    {
        return (string)$this->get('duration_unity') ?: 'year';
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
     * Récupération de l'offre associée.
     *
     * @return QueryOffer|null
     */
    public function getOffer(): ?QueryOffer
    {
        return ($id = (int)$this->get('offer_id')) ? $this->subscription()->offer()->get($id) : null;
    }

    /**
     * Récupération du nombre de jours de la période de ré-engagement.
     *
     * @return int
     */
    public function getRenewableDays(): int
    {
        return (int)$this->get('renewable_days', 0) ?: 0;
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

        $date->subDays($this->getRenewableDays());

        return $date;
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
     * Récupération du client associé.
     *
     * @return SubscriptionCustomer|null
     */
    public function getCustomer(): ?SubscriptionCustomer
    {
        return ($id = (int)$this->get('customer_id'))
            ? $this->subscription()->customer($id) : $this->subscription()->customer($this->get('customer_email'));
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
    public function isLimitationEnabled(): bool
    {
        if ($limited = $this->get('limited')) {
            return filter_var($limited, FILTER_VALIDATE_BOOLEAN);
        } else {
            return $this->subscription()->settings()->isOfferLimitationEnabled();
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
    public function isRenewNotify(): bool
    {
        return filter_var($this->get('renew_notification'), FILTER_VALIDATE_BOOLEAN);
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

        $end->subDays($this->getRenewableDays());

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

        $postdata['post_title'] = sprintf(__('Abonnement n°%s', 'tify'), $this->getId());

        foreach ($this->metasMap as $key => $metaKey) {
            $postdata['meta'][$metaKey] = $this->get($key);
        }

        $this->save($postdata);
    }
}