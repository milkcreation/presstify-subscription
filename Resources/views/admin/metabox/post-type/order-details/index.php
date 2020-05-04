<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<div class="ThemeContainerFluid">
    <div class="ThemeRow">
        <div class="ThemeCol-6">
            <h3 class="Form-title"><?php printf(__('Détails de la commande n°%d', 'tify'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Date de création', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($date = $order->getDatetime()) ? $date->format('d/m/Y H:i:s') : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Statut', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getStatus()->getLabel(); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Client', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($u = $order->getCustomer()->getUser())
                            ? $u->getDisplayName() . '<br>' . sprintf('(#%d - %s)', $u->getId(), partial('tag', [
                                'attrs'   => [
                                    'href'  => 'mailto:' . $u->getEmail(),
                                    'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $u->getDisplayName()),
                                ],
                                'content' => $u->getEmail(),
                                'tag'     => 'a',
                            ])) . '<br><br>' . partial('tag', [
                                'attrs'   => [
                                    'class' => 'button-secondary',
                                    'href'  => $u->getEditUrl(),
                                    'title' => sprintf(__('Éditer l\'utilisateur %s', 'tify'), $u->getDisplayName()),
                                ],
                                'content' => __('Éditer', 'tify'),
                                'tag'     => 'a',
                            ])
                            : partial('tag', [
                                'attrs'   => [
                                    'href'  => 'mailto:' . $order->getCustomer()->getEmail(),
                                    'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $order->getCustomer()->getEmail()),
                                ],
                                'content' => $order->getCustomer()->getEmail(),
                                'tag'     => 'a',
                            ]);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Abonnement', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($s = $order->getSubscription())
                            ? partial('tag', [
                                'attrs'   => [
                                    'class' => 'button-secondary',
                                    'href'  => $s->getEditUrl(),
                                    'title' => sprintf(__('Editer l\'abonnement %s', 'tify'), $s->getTitle()),
                                ],
                                'content' => sprintf(__('Abonnement n°%d', 'tify'), $s->getId()),
                                'tag'     => 'a',
                            ])
                            : '--';
                        ?>
                    </td>
                </tr>
            </table>

            <h3 class="Form-title"><?php printf(__('Informations de paiement', 'tify'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Identifiant de transaction', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getTransactionId() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Moyen de paiement', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getPaymentMethodTitle() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Premiers numéros de carte', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($first = $order->getCardFirst()) ? sprintf('%s ...', $first) : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Derniers numéros de carte', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($last = $order->getCardLast()) ? sprintf('... %s', $last) : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Validité de la carte', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($valid = $order->getCardValid()) ? $valid : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Date de réglement', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo ($date = $order->getPaymentDatetime()) ? $date->format('d/m/Y H:i:s') : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Montant', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getTotalHtml(); ?>
                    </td>
                </tr>
                <?php if ($order->getTotalTax()) : ?>
                    <tr>
                        <th>
                            <?php _e('Montant de TVA', 'tify'); ?>
                        </th>
                        <td>
                            <?php echo $order->getTotalTaxHtml(); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <h3 class="Form-title"><?php printf(__('Informations de connection', 'tify'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Adresse IP du client', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getCustomerIp() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Navigateur', 'tify'); ?>
                    </th>
                    <td>
                        <?php echo $order->getCustomerUserAgent() ?: '--'; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="ThemeCol-6">
            <h3 class="Form-title"><?php _e('Toute l\'activité', 'tify'); ?></h3>
            <?php if ($notes = $order->getNotes()) : ?>
                <ul class="Order-notes">
                    <?php foreach ($notes as $note) : ?>
                        <li class="Order-note">
                            <div class="Order-noteContent">
                                <?php echo $note->getContent(); ?>
                            </div>
                            <div class="Order-noteDate">
                                <?php echo $note->getDateTime()->format('d/m/Y H:i:s'); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="Order-notes Order-notes--empty">
                    <?php echo partial('notice', [
                        'content' => __('Cette commande ne présente aucune activité pour le moment.', 'tify'),
                        'type'    => 'info',
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>