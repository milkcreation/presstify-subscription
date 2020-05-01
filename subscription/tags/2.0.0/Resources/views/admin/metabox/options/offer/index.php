<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<h3 class="Form-title"><?php _e('Gestion de l\'engagement', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activer', 'theme'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[duration][enabled]',
                'value' => $settings->isOfferDurationEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Gestion du ré-engagement', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activer', 'theme'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[renew][enabled]',
                'value' => $settings->isOfferRenewEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>