<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Offer;

use tiFy\Column\AbstractColumnDisplayPostTypeController;
use tiFy\Plugins\Subscription\Proxy\Subscription;

class OfferDetailsColumn extends AbstractColumnDisplayPostTypeController
{
    /**
     * @inheritDoc
     */
    public function header()
    {
        return $this->item->getTitle() ? : __('Détails', 'theme');
    }

    /**
     * @inheritDoc
     */
    public function content($column_name = null, $post_id = null, $null = null)
    {
        $offer = QueryOffer::create($post_id);
        $settings = Subscription::settings();

        return $this->viewer('index', compact('offer', 'settings'));
    }
}