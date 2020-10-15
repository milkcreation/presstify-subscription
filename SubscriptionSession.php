<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use BadMethodCallException;
use Exception;
use tiFy\Contracts\Session\Store;
use tiFy\Plugins\Subscription\Order\QueryOrder;
use tiFy\Support\Proxy\Session;

/**
 * @mixin \tiFy\Session\Store
 */
class SubscriptionSession
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Instance de la session.
     * @var Store
     */
    private $store;

    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): self
    {
        if (!$this->booted) {
            $this->booted = true;
        }

        return $this;
    }

    /**
     * Délégation d'appel des méthodes du controleur de données de session associé.
     *
     * @param string $name Nom de la méthode à appeler.
     * @param array $arguments Liste des variables passées en argument.
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments)
    {
        try {
            return $this->store()->$name(...$arguments);
        } catch (Exception $e) {
            throw new BadMethodCallException(sprintf(
                    __('La méthode de session [%s] n\'est pas disponible.', 'tify'), $name)
            );
        }
    }

    /**
     * Définition d'une commande en attente de paiement.
     *
     * @return QueryOrder|null
     */
    public function createOrder(): ?QueryOrder
    {
        if ($order = QueryOrder::insert()) {
            $this->put('order_awaiting_payment', $order->getId());
        }

        return $order;
    }

    /**
     * Récupération de la commande en attente de paiement.
     *
     * @return QueryOrder|null
     */
    public function getOrder(): ?QueryOrder
    {
        if (!$order_id = $this->get('order_awaiting_payment', 0)) {
            return null;
        }

        return $this->subscription()->order()->get($order_id);
    }

    /**
     * Récupération de l'instance de la session.
     *
     * @return Store
     */
    public function store(): Store
    {
        if (is_null($this->store)) {
            $this->store = Session::registerStore('subscription');
        }

        return $this->store;
    }
}