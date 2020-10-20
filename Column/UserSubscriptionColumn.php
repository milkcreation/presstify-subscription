<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Column;

use tiFy\Column\AbstractColumnDisplayUserController;
use tiFy\Support\Proxy\PostType;

class UserSubscriptionColumn extends AbstractColumnDisplayUserController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ? : PostType::get('subscription')->label('singular_name');
    }

    /**
     * @inheritDoc
     */
    public function content($content = null, $column_name = null, $user_id = null)
    {
        $customer = subscription()->customer((int)$user_id);

        return $this->viewer('index', compact('customer'));
    }
}