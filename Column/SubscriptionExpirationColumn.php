<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Column;

use tiFy\Plugins\Subscription\QuerySubscription;
use tiFy\Column\AbstractColumnDisplayPostTypeController;
use tiFy\Support\Proxy\Partial;

class SubscriptionExpirationColumn extends AbstractColumnDisplayPostTypeController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ? : __('Expir.', 'tify');
    }

    /**
     * @inheritDoc
     */
    public function content($column_name = null, $post_id = null, $null = null)
    {
        /** @var QuerySubscription|null $qs */
        $qs = QuerySubscription::create($post_id);

        $class = 'SubscriptionIndicator';
        if ($qs->isExpired()) {
            $class .= ' SubscriptionIndicator--expired';
            $title = __('Expiré', 'tify');
        } elseif ($qs->isRenewable()) {
            $class .= ' SubscriptionIndicator--renewable';
            $title = __('Renouvelable', 'tify');
        } elseif ($qs->isActive()) {
            $class .= ' SubscriptionIndicator--active';
            $title = __('Actif', 'tify');
        }

        return Partial::get('tag', [
            'attrs' => [
                'id' => 'SubscriptionIndicator--' . $qs->getId(),
                'class' => $class,
                'href'  => '#SubscriptionIndicator--' . $qs->getId(),
                //'title' => $title . ' - ' .sprintf(__('Fin de l\'abonnement : %s'), $qs->getEndDate()->format('d/m/Y'))
            ],
            'content' => '',
            'tag' => 'a'
        ]);
    }
}