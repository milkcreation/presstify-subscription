<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<table class="Form-table">
    <tr>
        <th><?php _e('Nom de famille', 'theme'); ?></th>
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
        <th><?php _e('Prénom', 'theme'); ?></th>
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
        <th><?php _e('Société', 'theme'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => '%s widefat',
                    'readonly'
                ],
                'value' => $order->getBilling('company')
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Adresse', 'theme'); ?></th>
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
        <th><?php _e('Adresse complémentaire', 'theme'); ?></th>
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
        <th><?php _e('Ville', 'theme'); ?></th>
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
        <th><?php _e('Code postal', 'theme'); ?></th>
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
        <th><?php _e('Numéro de téléphone', 'theme'); ?></th>
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
        <th><?php _e('Adresse de messagerie', 'theme'); ?></th>
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