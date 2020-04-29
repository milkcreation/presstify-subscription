<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\ExportOrder;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Template\Templates\PostListTable\Db as BaseDb;

class Db extends BaseDb
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    public function newQuery()
    {
        return parent::newQuery()->type('subscription-order')->whereIn(
            'post_status', $this->subscription()->order()->statusPaymentCompleteNames()
        );
    }
}
