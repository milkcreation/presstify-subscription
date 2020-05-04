<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Column;

use tiFy\Plugins\Subscription\QuerySubscription;
use tiFy\Column\AbstractColumnDisplayPostTypeController;

class SubscriptionDetailsColumn extends AbstractColumnDisplayPostTypeController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ? : __('Détails', 'tify');
    }

    /**
     * @inheritDoc
     */
    public function content($column_name = null, $post_id = null, $null = null)
    {
        /** @var QuerySubscription|null $post */
        $subscription = QuerySubscription::create($post_id);

        return $this->viewer('index', compact('subscription'));
    }
}