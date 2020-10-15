<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<em>
    <span class="dashicons dashicons-info-outline"></span>
    <?php _e('Message de notification d\'abonnement à destination des gestionnaires.', 'tify'); ?>
</em>
<hr>
<table class="form-table">
    <tbody>
    <tr>
        <th scope="row"><?php _e('Activation', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[enabled]',
                'value' => $settings->isOrderNotificationEnabled() ? 'on' : 'off'
            ]); ?>
        </td>
    </tr>
    </tbody>
</table>

<h3><?php _e('Expéditeur de la notification', 'tify'); ?></h3>
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

<h3><?php _e('Destinataires de la notification', 'tify'); ?></h3>
<div>
    <em>
        <?php printf(
            __('Par défaut : <b>%s</b>, si aucun destinataire n\'est renseigné.', 'tify'),
            join(' - ', $settings->getDefaultEmail())
        ); ?>
    </em>
    <?php echo field('repeater', [
        'button' => [
            'content' => __('Ajouter un destinataire', 'tify'),
        ],
        'name'   => $this->name() . '[recipients]',
        'value'  => $this->value('recipients'),
        'viewer' => [
            'override_dir' => dirname($this->path()) . '/repeater',
        ],
    ]); ?>
</div>