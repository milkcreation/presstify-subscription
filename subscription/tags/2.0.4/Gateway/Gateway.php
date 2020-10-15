<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Gateway;

use Illuminate\Support\Collection;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Plugins\Subscription\Contracts\PaymentGateway;

class Gateway
{
    use SubscriptionAwareTrait;

    /**
     * Liste des instances de plateforme de paiement déclarées.
     * @var PaymentGateway[]|array
     */
    protected $paymentGateway = [];

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Liste des paramètres des plateformes de paiement.
     * @var array[]
     */
    protected $params = [];

    /**
     * Récupération de la liste des plateformes de paiement disponible.
     *
     * @return PaymentGateway[]|array
     */
    public function available(): array
    {
        return (new Collection($this->paymentGateway))->filter(function (PaymentGateway $item, $name) {
            return ($name === $item->getName()) && $item->isEnabled();
        })->all();
    }

    /**
     * Initialisation.
     *
     * @return $this
     */
    public function boot(): self
    {
        if (!$this->booted) {
            if ($params = $this->subscription()->config('gateway', [])) {
                foreach($params as $name => $attrs) {
                    if (is_numeric($name)) {
                        if (is_string($attrs)) {
                            $this->params[$attrs] = [];
                        }
                    } else {
                        $this->params[$name] = is_array($attrs) ? $attrs : [];
                    }
                }
            }

            foreach($this->params as $name => $attrs) {
                if ($this->subscription()->resolvable("gateway.{$name}")) {
                    $this->set($name, $this->subscription()->resolve("gateway.{$name}"));
                }
            }

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Récupération d'une plateforme de paiement.
     *
     * @param string $name
     *
     * @return PaymentGateway
     */
    public function get(string $name): ?PaymentGateway
    {
        return $this->paymentGateway[$name] ?? null;
    }

    /**
     * Définition d'une plateforme de paiement.
     *
     * @param string $name
     * @param PaymentGateway $paymentGateway
     *
     * @return $this
     */
    public function set(string $name, PaymentGateway $paymentGateway)
    {
        $this->paymentGateway[$name] = $paymentGateway
            ->setName($name)
            ->setSubscription($this->subscription)
            ->setParams($this->params[$name] ?: [])
            ->boot();

        return $this;
    }
}