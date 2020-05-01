<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Gateway;

use tiFy\Plugins\Subscription\Contracts\PaymentGateway;
use tiFy\Plugins\Subscription\{
    Order\QueryOrder,
    SubscriptionAwareTrait
};
use tiFy\Support\ParamsBag;

class AbstractPaymentGateway implements PaymentGateway
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'intialisation.
     * @var bool
     */
    protected $booted = false;

    /**
     * Instance des paramètres de configuration.
     * @var ParamsBag|null
     */
    protected $params;

    /**
     * Instance de la plateforme de paiement associée.
     * @var QueryOrder
     */
    protected $order;

    /**
     * @inheritDoc
     */
    public function boot(): PaymentGateway
    {
        if (!$this->booted) {
            $this->booted = true;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function capturePayment(array $params = []): void
    {
        if (!$order = $this->getOrder()) {
            return;
        } else {
            if ($transaction_id = $params['transaction_id'] ?? null) {
                unset($params['transaction_id']);
                $order->set(compact('transaction_id'));
            }

            if ($date_paid = $params['date_paid'] ?? null) {
                unset($params['date_paid']);
                $order->set(compact('date_paid'));
            }

            foreach($params as $key => $value) {
                $order->set($key, $value);
            }

            $order->update();
        }
    }

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return [
            /**
             * Activation de la plateforme de paiement.
             * @var bool
             */
            'enabled' => true
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPaymentForm(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): ?QueryOrder
    {
        return $this->order;
    }

    /**
     * @inheritDoc
     */
    public function handleFailed(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function handleCancelled(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function handleIpn(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function handlePending(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function handleSuccessed(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return (bool)$this->params('enabled', true);
    }

    /**
     * @inheritDoc
     */
    public function params($key = null, $default = null)
    {
        if (!$this->params instanceof ParamsBag) {
            $this->params = (new ParamsBag())->set($this->defaults());
        }

        if (is_string($key)) {
            return $this->params->get($key, $default);
        } elseif (is_array($key)) {
            return $this->params->set($key);
        } else {
            return $this->params;
        }
    }

    /**
     * @inheritDoc
     */
    public function setParams(array $params): PaymentGateway
    {
        $this->params($params);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setOrder(QueryOrder $order): PaymentGateway
    {
        $this->order = $order;

        return $this;
    }
}