<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<h3 class="Form-title"><?php _e('Gestion de l\'engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activer', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[limited][enabled]',
                'value' => $settings->isOfferLimitedEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>
<table class="Form-table LimitedEnabled<?php echo $settings->isOfferLimitedEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Durée de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'min'   => 1,
                    'style' => 'width:80px;vertical-align:middle;',
                ],
                'name'  => $this->name() . '[limited][length]',
                'value' => $settings->getOfferLimitedLength(),
            ]); ?>
            <?php echo field('select-js', [
                'attrs'   => [
                    'style' => 'display:inline-block;vertical-align:middle;',
                ],
                'choices' => [
                    'year'  => __('Année(s)', 'tify'),
                    'month' => __('Mois', 'tify'),
                    'day'   => __('Jour(s)', 'tify'),
                ],
                'name'    => $this->name() . '[limited][unity]',
                'value'   => $settings->getOfferLimitedUnity(),
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Gestion du ré-engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activer', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[renew][enabled]',
                'value' => $settings->isOfferRenewEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>
<table class="Form-table LimitedEnabled<?php echo $settings->isOfferRenewEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Possible à partir de', 'tify'); ?></th>
        <td>
            <?php printf(__('%s jours avant la fin de l\'abonnement en cours.', 'tify'), field('number', [
                'attrs' => [
                    'min'   => 1,
                    'style' => 'width:80px;',
                ],
                'name'  => $this->name() . '[renew][days]',
                'value' => $settings->getOfferRenewDays(),
            ])); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Envoyer une notification par mail à l\'abonné', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[renew][notify]',
                'value' => $settings->isOfferRenewNotify() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>