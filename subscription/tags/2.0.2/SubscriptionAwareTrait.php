<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

trait SubscriptionAwareTrait
{
    /**
     * Instance du gestionnaire d'abonnements.
     * @var Subscription
     */
    protected $subscription;

    /**
     * Récupération de l'instance du gestionnaire d'abonnements
     *
     * @return Subscription|null
     */
    public function subscription(): ?Subscription
    {
        if (!is_null($this->subscription)) {
            return $this->subscription;
        } else {
            $this->subscription = subscription() ? : null;
        }

        return $this->subscription;
    }

    /**
     * Définition de l'instance du gestionnaire d'abonnements.
     *
     * @param Subscription $subscription
     *
     * @return static
     */
    public function setSubscription(Subscription $subscription): self
    {
        $this->subscription = $subscription;

        return $this;
    }
}