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

<?php if ($settings->isOfferLimitationEnabled()) : ?>
<h3 class="Form-title"><?php _e('Engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Durée de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'min' => 0,
                ],
                'name'  => '_duration_length',
                'value' => $offer->getDurationLength()
            ]); ?>
            <?php echo field('select-js', [
                'choices' => [
                    'year'  => __('Année(s)', 'tify'),
                    'month' => __('Mois', 'tify'),
                    'day'   => __('Jour(s)', 'tify'),
                ],
                'name'    => '_duration_unity',
                'value' => $offer->getDurationUnity()
            ]); ?>
        </td>
    </tr>
</table>
<?php endif; ?>

<?php if ($settings->isOfferRenewEnabled()) : ?>
<h3 class="Form-title"><?php _e('Ré-engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Possible à partir de', 'tify'); ?></th>
        <td>
            <?php printf(__('%s jours avant la fin de l\'abonnement en cours.', 'tify'), field('number', [
                'attrs' => [
                     'min' => 0,
                ],
                'name'  => '_renewable_days',
                'value' => $offer->getRenewableDays()
            ])); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Envoyer une notification par mail à l\'abonné', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_renew_notification',
                'value' => $offer->isRenewNotify() === 'on' ? 'on': 'off'
            ]); ?>
        </td>
    </tr>
</table>
<?php endif;