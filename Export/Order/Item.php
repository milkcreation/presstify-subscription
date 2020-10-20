<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\Order;

use tiFy\Plugins\Subscription\{SubscriptionAwareTrait, Order\QueryOrder};
use tiFy\Template\Templates\ListTable\Contracts\Item as BaseItemContract;
use tiFy\Wordpress\Template\Templates\PostListTable\Item as BaseItem;

/**
 * @mixin QueryOrder
 */
class Item extends BaseItem
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    public function parse(): BaseItemContract
    {
        if ($order = $this->subscription()->order()->get($this->getKeyValue())) {
            $this->setDelegate($order);
        }

        parent::parse();

        return $this;
    }
}