<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<table class="Form-table">
    <tr>
        <th><?php _e('Nom de famille', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('lastname')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Prénom', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('firstname')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Adresse', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('address1')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Adresse complémentaire', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('address2')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Ville', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('city')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Code postal', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('postcode')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Numéro de téléphone', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('phone')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Adresse de messagerie', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('email')
            ]); ?>
        </td>
    </tr>
</table>