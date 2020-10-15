<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var WP_Post $wp_post
 * @var tiFy\Plugins\Subscription\Offer\QueryOffer $offer
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<h3 class="Form-title"><?php _e('Identification', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Intitulé', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => 'widefat',
                ],
                'name'  => '_label',
                'value' => $offer->getLabel()
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Unité de gestion de stock (EAN13, Réf ...)', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => 'widefat',
                ],
                'name'  => '_sku',
                'value' => $offer->getSku()
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Tarifs', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php printf(__('Prix (%s)', 'tify'), $this->params('tax_label')); ?></th>
        <td>
            <?php printf ('%s %s', field('number', [
                'attrs' => [
                    'min' => 0,
                ],
                'name' => '_price',
                'value' => $offer->getPrice()
            ]), $this->params('device')); ?>
        </td>
    </tr>
    <?php if ($this->params('price.taxable')) : ?>
    <tr>
        <th><?php _e('Montant de TVA', 'tify'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'max' => 100,
                    'min' => 0,
                    'step'=> '0.01'
                ],
                'name' => '_tax',
                'value' => $offer->getTax()
            ]); ?>%
        </td>
    </tr>
    <?php endif; ?>
</table>

<?php if ($settings->isOfferLimitedEnabled()) : ?>
<h3 class="Form-title"><?php _e('Engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activation', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_limited',
                'value' => $offer->isLimitedEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>
<table class="Form-table LimitedEnabled<?php echo $offer->isLimitedEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Durée de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'min' => 0,
                    'style' => 'width:80px;vertical-align:middle;',
                ],
                'name'  => '_limited_length',
                'value' => $offer->getLimitedLength()
            ]); ?>
            <?php echo field('select', [
                'attrs'   => [
                    'style' => 'display:inline-block;vertical-align:middle;',
                ],
                'choices' => [
                    'year'  => __('Année(s)', 'tify'),
                    'month' => __('Mois', 'tify'),
                    'day'   => __('Jour(s)', 'tify'),
                ],
                'name'    => '_limited_unity',
                'value' => $offer->getLimitedUnity()
            ]); ?>
        </td>
    </tr>
</table>
<?php endif; ?>

<?php if ($settings->isOfferRenewEnabled()) : ?>
<h3 class="Form-title"><?php _e('Ré-engagement', 'tify'); ?></h3>
    <table class="Form-table">
        <tr>
            <th><?php _e('Activation', 'tify'); ?></th>
            <td>
                <?php echo field('toggle-switch', [
                    'name'  => '_renewable',
                    'value' => $offer->isRenewEnabled() ? 'on' : 'off',
                ]); ?>
            </td>
        </tr>
    </table>
<table class="Form-table LimitedEnabled<?php echo $offer->isRenewEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Possible à partir de', 'tify'); ?></th>
        <td>
            <?php printf(__('%s jours avant la fin de l\'abonnement en cours.', 'tify'), field('number', [
                'attrs' => [
                     'min' => 0,
                ],
                'name'  => '_renew_days',
                'value' => $offer->getRenewDays()
            ])); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Envoyer une notification par mail à l\'abonné', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_renew_notify',
                'value' => $offer->isRenewNotifyEnabled() ? 'on': 'off'
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Jours avant l\'expédition du rappel', 'tify'); ?></th>
        <td>
            <?php printf('%s jours avant l\'expiration de l\'abonnement.', field('number', [
                'attrs' => [
                    'max' => $offer->getRenewDays(),
                    'min' => 1
                ],
                'name'  => '_renew_notify_days',
                'value' => $offer->getRenewNotifyDays()
            ])->render()); ?>
        </td>
    </tr>
</table>
<?php endif;