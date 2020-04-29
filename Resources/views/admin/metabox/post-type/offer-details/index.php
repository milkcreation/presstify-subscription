<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var WP_Post $wp_post
 */
?>
<h3 class="Form-title"><?php _e('Identification', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Intitulé', 'theme'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => 'widefat',
                ],
                'name'  => '_label',
                'value' => get_post_meta($wp_post->ID, '_label', true)
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Unité de gestion de stock (EAN13, Réf ...)', 'theme'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => 'widefat',
                ],
                'name'  => '_sku',
                'value' => get_post_meta($wp_post->ID, '_sku', true)
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Engagement', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Durée de l\'abonnement', 'theme'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'min' => 0,
                ],
                'name'  => '_duration_length',
                'value' => get_post_meta($wp_post->ID, '_duration_length', true)
            ]); ?>
            <?php echo field('select-js', [
                'choices' => [
                    'year'  => __('Année(s)', 'theme'),
                    'month' => __('Mois', 'theme'),
                    'day'   => __('Jour(s)', 'theme'),
                ],
                'name'    => '_duration_unity',
                'value' => get_post_meta($wp_post->ID, '_duration_unity', true)
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Tarifs', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php printf(__('Prix (%s)', 'theme'), $this->params('tax_label')); ?></th>
        <td>
            <?php printf ('%s %s', field('number', [
                'attrs' => [
                    'min' => 0,
                ],
                'name' => '_price',
                'value' => get_post_meta($wp_post->ID, '_price', true)
            ]), $this->params('device')); ?>
        </td>
    </tr>
    <?php if ($this->params('taxable')) : ?>
    <tr>
        <th><?php _e('Montant de TVA', 'theme'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'max' => 100,
                    'min' => 0,
                    'step'=> '0.01'
                ],
                'name' => '_tax',
                'value' => get_post_meta($wp_post->ID, '_tax', true)
            ]); ?>%
        </td>
    </tr>
    <?php endif; ?>
</table>

<h3 class="Form-title"><?php _e('Ré-engagement', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Possible à partir de', 'theme'); ?></th>
        <td>
            <?php printf(__('%s jours avant la fin de l\'abonnement en cours.', 'theme'), field('number', [
                'attrs' => [
                     'min' => 0,
                ],
                'name'  => '_renewable_days',
                'value' => get_post_meta($wp_post->ID, '_renewable_days', true) ? : 0
            ])); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Envoyer une notification par mail à l\'abonné', 'theme'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_renew_notification',
                'value' => get_post_meta($wp_post->ID, '_renew_notification', true) === 'off' ? 'off': 'on'
            ]); ?>
        </td>
    </tr>
</table>