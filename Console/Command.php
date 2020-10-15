<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Console;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Support\Proxy\Console;

class Command
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): self
    {
        if (!$this->booted) {
            Console::add($this->subscription()->resolve('command.generate-order-number')
                ->setName('subscription:generate-order-number'));
            Console::add($this->subscription()->resolve('command.generate-subscription-number')
                ->setName('subscription:generate-subscription-number'));
            Console::add($this->subscription()->resolve('command.renew-notify')
                ->setName('subscription:renew-notify'));

            $this->booted = true;
        }

        return $this;
    }
}
