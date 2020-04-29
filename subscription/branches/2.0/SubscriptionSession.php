<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use BadMethodCallException;
use Exception;
use tiFy\Contracts\Session\Store;
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
            $this->store = Session::registerStore('subscription');

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
            return $this->store->$name(...$arguments);
        } catch (Exception $e) {
            throw new BadMethodCallException(sprintf(
                __('La méthode de session [%s] n\'est pas disponible.', 'tify'), $name)
            );
        }
    }
}