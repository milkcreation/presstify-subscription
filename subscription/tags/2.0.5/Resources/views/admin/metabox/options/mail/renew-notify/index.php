<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<em>
    <span class="dashicons dashicons-info-outline"></span>
    <?php _e('Message d\'invitation de ré-engagement à destination d\'un abonné.', 'tify'); ?>
</em>
<hr>
<table class="form-table">
    <tbody>
    <tr>
        <th scope="row"><?php _e('Activation', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[enabled]',
                'value' => $settings->isRenewNotifyEnabled() ? 'on' : 'off'
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Jours avant l\'expédition du rappel', 'tify'); ?></th>
        <td>
            <?php printf('%s jours avant l\'expiration de l\'abonnement.', field('number', [
                'attrs' => [
                    'max' => $settings->getOfferRenewDays(),
                    'min' => 1
                ],
                'name'  => $this->name() . '[days]',
                'value' => $this->value('days', $settings->getRenewNotifyDays())
            ])->render()); ?>
        </td>
    </tr>
    </tbody>
</table>

<h3><?php _e('Expéditeur de l\'invite de ré-engagement', 'tify'); ?></h3>
<em>
    <?php printf(
        __('Par défaut : <b>%s</b>, si aucun expéditeur n\'est renseigné.', 'tify'),
        join(' - ', $settings->getDefaultEmail())
    ); ?>
</em>
<table class="form-table">
    <tbody>
    <tr>
        <th scope="row"><?php _e('Email (requis)', 'tify'); ?></th>
        <td>
            <div class="ThemeInput--email">
                <?php echo field('text', [
                    'name'  => $this->name() . '[sender][email]',
                    'value' => $this->value('sender.email'),
                    'attrs' => [
                        'size'         => 40,
                        'autocomplete' => 'off'
                    ]
                ]); ?>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Nom (optionnel)', 'tify'); ?></th>
        <td>
            <div class="ThemeInput--user">
                <?php echo field('text', [
                    'name'  => $this->name() . '[sender][name]',
                    'value' => $this->value('sender.name'),
                    'attrs' => [
                        'size'         => 40,
                        'autocomplete' => 'off'
                    ]
                ]); ?>
            </div>
        </td>
    </tr>
    </tbody>
</table>