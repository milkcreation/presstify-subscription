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
                'value' => $order->getShipping('lastname')
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
                'value' => $order->getShipping('firstname')
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
                'value' => $order->getShipping('address1')
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
                'value' => $order->getShipping('address2')
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
                'value' => $order->getShipping('city')
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
                'value' => $order->getShipping('postcode')
            ]); ?>
        </td>
    </tr>
</table>