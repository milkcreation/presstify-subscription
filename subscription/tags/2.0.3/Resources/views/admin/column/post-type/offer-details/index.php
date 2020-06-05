<?php
/**
 * @var tiFy\Column\ColumnView $this
 * @var tiFy\Plugins\Subscription\Offer\QueryOffer $offer
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<dl class="OfferDetails">
    <dt><?php _e('Identification', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Intitulé', 'tify'); ?> : </label>
        <span><?php echo $offer->getLabel() ?: '--'; ?></span>
    </dd>
    <dd>
        <label><?php _e('Unité de gestion de stock (EAN13, Réf ...)', 'tify'); ?> : </label>
        <span><?php echo $offer->getSku() ?: '--'; ?></span>
    </dd>

    <?php if ($settings->isOfferLimitedEnabled()) : ?>
    <dt><?php _e('Engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Durée', 'tify'); ?> : </label>
        <span><?php echo $offer->getLimitedHtml(); ?></span>
    </dd>
    <?php endif; ?>

    <?php if ($settings->isOfferRenewEnabled()) : ?>
    <dt><?php _e('Ré-engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Possible', 'tify'); ?> : </label>
        <span>
            <?php printf(
                _nx('%d jour avant', '%d jours avant', $offer->getRenewDays(), 'tify'),
                $offer->getRenewDays()
            ); ?>
        </span>
    </dd>
    <dd>
        <label><?php _e('Notification de l\'abonné', 'tify'); ?> : </label>
        <span><?php $offer->isRenewNotify() ? _e('Oui', 'tify') : _e('Non', 'tify'); ?></span>
    </dd>
    <?php endif; ?>
</dl>