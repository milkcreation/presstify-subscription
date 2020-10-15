<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Column;

use tiFy\Plugins\Subscription\QuerySubscription;
use tiFy\Column\AbstractColumnDisplayPostTypeController;
use tiFy\Support\Proxy\Partial;

class SubscriptionCustomerColumn extends AbstractColumnDisplayPostTypeController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ?: __('Client', 'tify');
    }

    /**
     * @inheritDoc
     */
    public function content($column_name = null, $post_id = null, $null = null)
    {
        /** @var QuerySubscription|null $subscription */
        $subscription = QuerySubscription::create($post_id);

        return ($u = $subscription->getCustomer()->getUser())
            ? '<b>#' . $u->getId() . '</b> - ' . Partial::get('tag', [
                'attrs'   => [
                    'href'  => 'mailto:' . $u->getEmail(),
                    'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $u->getDisplayName()),
                    'style' => 'margin-bottom:5px; display:inline-block;'
                ],
                'content' => join(' - ', array_filter([$u->getDisplayName(), $u->getEmail()])),
                'tag'     => 'a'
            ]) . '<br>' . Partial::get('tag', [
                'attrs'   => [
                    'class' => 'button-secondary',
                    'href'  => $u->getEditUrl(),
                    'title' => sprintf(__('Editer l\'utilisateur %s', 'tify'), $u->getDisplayName())
                ],
                'content' => __('Éditer l\'utilisateur', 'tify'),
                'tag'     => 'a'
            ])
            : (($email = $subscription->getCustomerEmail()) ? Partial::get('tag', [
                'attrs'   => [
                    'href'  => 'mailto:' . $email,
                    'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $email)
                ],
                'content' => join(' - ', array_filter([$subscription->getCustomer()->getDisplayName(), $email])),
                'tag'     => 'a'
            ]) : '--');
    }
}