<?php
/**
 * @var tiFy\Column\ColumnView $this
 * @var tiFy\Plugins\Subscription\SubscriptionCustomer $customer
 */
?>
<?php if ($s = $customer->getSubscription()) : ?>
    <dl>
        <dt><?php _e('Identification', 'tify'); ?></dt>
        <dd>
            <label><?php _e('Intitulé', 'tify'); ?> : </label>
            <span><?php echo $s->getLabel() ?: '--'; ?></span>
        </dd>
        <dt><?php _e('Engagement', 'tify'); ?></dt>
        <dd>
            <label><?php _e('Actif', 'tify'); ?> : </label>
            <span>
                <?php echo $s->isLimitedEnabled() ? __('Oui', 'tify') : __('Non', 'tify'); ?>
            </span>
        </dd>
        <?php if ($s->isLimitedEnabled()) : ?>
            <dd>
                <label><?php _e('Durée de l\'engagement', 'tify'); ?> : </label>
                <span>
                <?php echo $s->getLimitedHtml() ?: '--' ?>
            </span>
            </dd>
            <dd>
                <label><?php _e('Début de l\'engagement', 'tify'); ?> : </label>
                <span>
                <?php echo ($date = $s->getStartDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
            </span>
            </dd>
            <dd>
                <label><?php _e('Fin de l\'engagement', 'tify'); ?> : </label>
                <span>
               <?php echo ($date = $s->getEndDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
            </span>
            </dd>
        <?php endif; ?>
        <dt><?php _e('Ré-engagement', 'tify'); ?></dt>
        <dd>
            <label><?php _e('Actif', 'tify'); ?> : </label>
            <span>
                <?php echo $s->isRenewEnabled() ? __('Oui', 'tify') : __('Non', 'tify'); ?>
            </span>
        </dd>
        <?php if ($s->isRenewEnabled()) : ?>
            <dd>
                <label><?php _e('A partir de', 'tify'); ?> : </label>
                <span>
                <?php echo ($days = $s->getRenewDays())
                    ? sprintf(_n('%d jour', '%d jours', $days, 'tify'), $days) : '--'; ?>
            </span>
            </dd>
            <dd>
                <label><?php _e('Mail de notification', 'tify'); ?> : </label>
                <span>
                <?php echo $s->isRenewNotifyEnabled() ? __('Oui', 'tify') : __('Non', 'tify'); ?>
            </span>
            </dd>
            <dd>
                <label><?php _e('Ré-engagé', 'tify'); ?> : </label>
                <span>
                <?php if (!$s->isRenewable()) :
                    _e('hors période', 'tify');
                else :
                    echo count($customer->getSubscriptions()) > 1 ? __('Oui', 'tify') : __('Non', 'tify');
                endif; ?>
            </span>
            </dd>
        <?php endif; ?>
    </dl>
    <br>
    <?php echo partial('tag', [
        'attrs'   => [
            'class' => 'button-secondary',
            'href'  => $s->getEditUrl(),
            'title' => sprintf(__('Édition de l\'abonnement n°%d', 'tify'), $s->getId()),
        ],
        'content' => __('Éditer l\'abonnement', 'tify'),
        'tag'     => 'a',
    ]); ?>
<?php else : ?>
    --
<?php endif;
