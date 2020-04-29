<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;

class Export
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
     * @return $this
     */
    public function boot(): self
    {
        if (!$this->booted) {

            /* TEMPLATES * /
            Template::set([
                'export-order' => (new ExportOrderTemplate())->setSubscription($this->subscription)
            ]);
            /**/

            $this->booted = true;
        }

        return $this;
    }
}