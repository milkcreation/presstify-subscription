<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\Plugins\Subscription\{Offer\QueryOffer, SubscriptionAwareTrait};
use tiFy\Support\{DateTime, ParamsBag};

class QueryOrderLineItem extends ParamsBag
{
    use SubscriptionAwareTrait;

    /**
     * Clé de hashage dans la commande associé.
     * @var string
     */
    protected $hash = '';

    /**
     * Instance de la commande associée.
     * @var QueryOrder
     */
    protected $order;

    /**
     * CONSTRUCTEUR.
     *
     * @param array $attrs
     *
     * @return void
     */
    public function __construct(array $attrs = [])
    {
        $this->setSubscription(subscription());

        $this->set($attrs)->parse();
    }

    /**
     * Récupération de la date de démarrage.
     *
     * @return DateTime
     */
    public function calcEndDate():? DateTime
    {
        if (!$end = $this->calcStartDate()) {
            return null;
        }

        $length = $this->getDurationLength();

        switch($this->getDurationUnity()) {
            default :
            case 'year' :
                $end->addYears($length);
                break;
            case 'month' :
                $end->addMonths($length);
                break;
            case 'days' :
                $end->addDays($length);
                break;
        }

        return $end;
    }

    /**
     * Récupération de la date de démarrage.
     *
     * @return DateTime
     */
    public function calcStartDate():? DateTime
    {
        if ($renewable = $this->order->getCustomer()->getRenewableSubscription()) {
            $date = $renewable->getEndDate();
        } else {
            $date = DateTime::now(DateTime::getGlobalTimeZone());
        }

        return isset($date) ? $date->setTime(0, 0, 0) : null;
    }

    /**
     * Récupération de la liste des produits associé à une commande.
     *
     * @param QueryOrder|int|string $order Instance|Identifiant de qualification|Clé de qualification de la commande.
     *
     * @return static[]|array
     */
    public static function fetchFromOrder($order): array
    {
        if(is_numeric($order)) {
            $order = QueryOrder::createFromId((int) $order);
        } elseif (is_string($order)) {
            $order = QueryOrder::createFromOrderKey($order);
        }

        if ($order instanceof QueryOrder) {
            $lineItems = [];
            foreach($order->get('line_items', []) as $id => $attrs) {
                $item = (new static($attrs))->setOrder($order);
                if (!is_numeric($id)) {
                    $item->setHash($id);
                }

                $lineItems[] = $item;
            }

            return $lineItems;
        } else {
            return [];
        }
    }

    /**
     * Génération de la clé de hashage du produit dans une commande.
     *
     * @return string
     */
    public function generateHash()
    {
        return md5(json_encode($this->all()));
    }

    /**
     * Récupération de la durée de l'abonnement
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
        return (string)$this->get('duration_unity', 'year');
    }

    /**
     * Récupération de la clé de hashage du produit dans la commande.
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * Récupération de l'intitulé de qualification.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->get('label', $this->getOffer()->getLabel() ?: '');
    }

    /**
     * Récupération du nom de qualification.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->get('name', $this->getOffer()->getTitle() ?: '');
    }

    /**
     * Récupération de la commande associée.
     *
     * @return QueryOrder
     */
    public function getOrder(): ?QueryOrder
    {
        if (is_null($this->order)) {
            $this->order = QueryOrder::createFromId((int)$this->get('order_id', 0));
        }

        return $this->order;
    }

    /**
     * Récupération du produit associé.
     *
     * @return QueryOffer
     */
    public function getOffer(): ?QueryOffer
    {
        return $this->subscription()->offer()->get($this->getOfferId());
    }

    /**
     * Récupération de l'identifiant de qualification du produit associé.
     *
     * @return int
     */
    public function getOfferId(): int
    {
        return (int)$this->get('offer_id', 0);
    }

    /**
     * Récupération du nombre de jours de la période le ré-engagement.
     *
     * @return int
     */
    public function getRenewableDays(): int
    {
        return (int)$this->get('renewable_days', 0);
    }

    /**
     * Vérification de l'envoi d'un mail de notification lors de la période de ré-engagement.
     *
     * @return bool
     */
    public function isRenewNotify(): bool
    {
        return (bool)$this->get('renew_notification', false);
    }

    /**
     * Définition de la clé de hashage dans la commande associée.
     *
     * @param string $hash
     *
     * @return static
     */
    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Définition de la commande associée.
     *
     * @param QueryOrder $order
     *
     * @return static
     */
    public function setOrder(QueryOrder $order): self
    {
        $this->order = $order;

        return $this;
    }
}