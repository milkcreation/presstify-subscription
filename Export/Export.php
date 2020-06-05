<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export;

use tiFy\Plugins\Subscription\{Export\Order\ListTableFactory, SubscriptionAwareTrait};
use tiFy\Support\Proxy\Template;

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

            /* TEMPLATES */
            Template::set([
                'export-order' => (new ListTableFactory())->setSubscription($this->subscription)
            ]);
            /**/

            $this->booted = true;
        }

        return $this;
    }
}