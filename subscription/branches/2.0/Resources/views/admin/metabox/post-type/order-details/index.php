<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<div class="ThemeContainerFluid">
    <div class="ThemeRow">
        <div class="ThemeCol-6">
            <h3 class="Form-title"><?php printf(__('Détails de la commande n°%d', 'theme'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Date de création', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo ($date = $order->getDatetime()) ? $date->format('d/m/Y H:i:s') : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Statut', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getStatus()->getLabel(); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Client', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo ($u = $order->getCustomer())
                            ? sprintf('%s (#%d - %s)', $u->getDisplayName(), $u->getId(), partial('tag', [
                                'attrs'   => [
                                    'href'  => $u->getEditUrl(),
                                    'title' => sprintf(__('Editer l\'utilisateur %s', 'theme'), $u->getDisplayName()),
                                ],
                                'content' => $u->getEmail(),
                                'tag'     => 'a',
                            ]))
                            : '--';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Abonnement', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo ($s = $order->getSubscription())
                            ? partial('tag', [
                                'attrs'   => [
                                    'href'  => $s->getEditUrl(),
                                    'title' => sprintf(__('Editer l\'abonnement %s', 'theme'), $s->getTitle()),
                                ],
                                'content' => sprintf(__('Abonnement n°%d', 'theme'), $s->getId()),
                                'tag'     => 'a',
                            ])
                            : '--';
                        ?>
                    </td>
                </tr>
            </table>

            <h3 class="Form-title"><?php printf(__('Informations de paiement', 'theme'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Identifiant de transaction', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getTransactionId() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Moyen de paiement', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getPaymentMethodTitle() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('4 derniers numéros de carte', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo ($last4 = $order->getCardLast4()) ? sprintf('... %s', $last4) : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Date de réglement', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo ($date = $order->getPaymentDatetime()) ? $date->format('d/m/Y H:i:s') : '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Montant', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getTotalHtml(); ?>
                    </td>
                </tr>
                <?php if ($order->getTotalTax()) : ?>
                    <tr>
                        <th>
                            <?php _e('Montant de TVA', 'theme'); ?>
                        </th>
                        <td>
                            <?php echo $order->getTotalTaxHtml(); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th>
                        <?php _e('Session de paiement Stripe', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo field('text', [
                            'attrs' => [
                                'class' => 'widefat',
                                $order->getStripePaymentIntentId() ? 'disabled' : null,
                            ],
                            'name'  => '_stripe_payment_intent',
                            'value' => $order->getStripePaymentIntentId(),
                        ]); ?>
                    </td>
                </tr>
            </table>

            <h3 class="Form-title"><?php printf(__('Informations de connection', 'theme'), $order->getId()); ?></h3>
            <table class="Form-table">
                <tr>
                    <th>
                        <?php _e('Adresse IP du client', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getCustomerIp() ?: '--'; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Navigateur', 'theme'); ?>
                    </th>
                    <td>
                        <?php echo $order->getCustomerUserAgent() ?: '--'; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="ThemeCol-6">
            <h3 class="Form-title"><?php _e('Toute l\'activité', 'theme'); ?></h3>
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
                        'content' => __('Cette commande ne présente aucune activité pour le moment.', 'theme'),
                        'type'    => 'info',
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
