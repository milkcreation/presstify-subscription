<?php
/**
 * @var tiFy\Column\ColumnView $this
 * @var tiFy\Plugins\Subscription\Offer\QueryOffer $offer
 */
?>
<dl class="OfferDetails">
    <dt><?php _e('Identification', 'theme'); ?></dt>
    <dd>
        <label><?php _e('Intitulé', 'theme'); ?> : </label>
        <span><?php echo $offer->getLabel() ?: '--'; ?></span>
    </dd>
    <dd>
        <label><?php _e('Unité de gestion de stock (EAN13, Réf ...)', 'theme'); ?> : </label>
        <span><?php echo $offer->getSku() ?: '--'; ?></span>
    </dd>
    <dt><?php _e('Engagement', 'theme'); ?></dt>
    <dd>
        <label><?php _e('Durée', 'theme'); ?> : </label>
        <span><?php echo $offer->getDurationHtml(); ?></span>
    </dd>
    <dt><?php _e('Ré-engagement', 'theme'); ?></dt>
    <dd>
        <label><?php _e('Possible', 'theme'); ?> : </label>
        <span>
            <?php printf(
                _nx('%d jour avant', '%d jours avant', $offer->getRenewableDays(), 'theme'),
                $offer->getRenewableDays()
            ); ?>
        </span>
    </dd>
    <dd>
        <label><?php _e('Notification de l\'abonné', 'theme'); ?> : </label>
        <span><?php $offer->isRenewNotify() ? _e('Oui', 'theme') : _e('Non', 'theme'); ?></span>
    </dd>
</dl>