<?php
/**
 * @var tiFy\Column\ColumnView $this
 * @var tiFy\Plugins\Subscription\Offer\QueryOffer $offer
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<ul class="OfferPrice">
    <li>
        <label><?php _e('Prix de vente', 'theme'); ?> : </label>
        <span>
            <big>
            <b>
                <?php echo$offer->getPriceHtml(1, [
                    'tax_label' => ' ' . $offer->getTaxLabel(),
                ]); ?>
            </b>
            </big>
        </span>
    </li>
    <?php if ($offer->isTaxable()) : ?>
        <li>
            <label><?php _e('Montant de TVA', 'theme'); ?> : </label>
            <span><?php printf('%s%%', $offer->getTax()); ?></span>
        </li>
    <?php endif; ?>
</ul>