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

    <?php if ($offer->isLimitedEnabled()) : ?>
        <dt><?php _e('Engagement', 'tify'); ?></dt>
        <dd>
            <label><?php _e('Durée', 'tify'); ?> : </label>
            <span><?php echo $offer->getLimitedHtml(); ?></span>
        </dd>
    <?php endif; ?>

    <dt><?php _e('Ré-engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Actif', 'tify'); ?> : </label>
        <span><?php $offer->isRenewEnabled() ? printf(
                _nx('%d jour avant', '%d jours avant expiration', $offer->getRenewDays(), 'tify'),
                $offer->getRenewDays()
            ) : _e('Non', 'tify'); ?></span>
    </dd>
    <?php if ($offer->isRenewEnabled()) : ?>
        <hr>
        <dd>
            <label><?php _e('Mail de rappel', 'tify'); ?> : </label>
            <span><?php $offer->isRenewNotifyEnabled() ? _e('Oui', 'tify') : _e('Non', 'tify'); ?></span>
        </dd>
        <dd>
            <label><?php _e('Expédié', 'tify'); ?> : </label>
            <span><?php printf(_n(
                '%d jour avant expiration', '%d jours avant expiration', $offer->getRenewNotifyDays(), 'tify'
            ), $offer->getRenewNotifyDays()); ?></span>
        </dd>
    <?php endif; ?>
</dl>