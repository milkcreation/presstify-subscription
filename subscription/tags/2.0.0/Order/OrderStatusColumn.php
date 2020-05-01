<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\Column\AbstractColumnDisplayPostTypeController;

class OrderStatusColumn extends AbstractColumnDisplayPostTypeController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ? : __('État', 'theme');
    }

    /**
     * @inheritDoc
     */
    public function content($column_name = null, $post_id = null, $null = null)
    {
        $order = QueryOrder::create($post_id);

        return $this->viewer('index', compact('order'));
    }
}