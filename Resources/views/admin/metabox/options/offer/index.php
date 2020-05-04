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
                'name'  => $this->name() . '[limitation][enabled]',
                'value' => $settings->isOfferLimitationEnabled() ? 'on' : 'off',
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