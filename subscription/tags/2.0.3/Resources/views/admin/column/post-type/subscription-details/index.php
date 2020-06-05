<?php
/**
 * @var tiFy\Column\ColumnView $this
 * @var tiFy\Plugins\Subscription\QuerySubscription $subscription
 */
?>
<dl>
    <dt><?php _e('Identification', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Intitulé', 'tify'); ?> : </label>
        <span><?php echo $subscription->getLabel() ?: '--'; ?></span>
    </dd>

    <dt><?php _e('Engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Actif', 'tify'); ?> : </label>
        <span>
            <?php echo $subscription->isLimitedEnabled() ? __('Oui', 'tify') : __('Non', 'tify') ?>
        </span>
    </dd>
    <?php if ($subscription->isLimitedEnabled()) : ?>
    <dd>
        <label><?php _e('Durée de l\'abonnement', 'tify'); ?> : </label>
        <span>
            <?php echo $subscription->getLimitedHtml() ? : '--' ?>
        </span>
    </dd>
    <dd>
        <label><?php _e('Début de l\'abonnement', 'tify'); ?> : </label>
        <span>
            <?php echo ($date = $subscription->getStartDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
        </span>
    </dd>
    <dd>
        <label><?php _e('Fin de l\'abonnement', 'tify'); ?> : </label>
        <span>
           <?php echo ($date = $subscription->getEndDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
        </span>
    </dd>
    <?php endif; ?>

    <dt><?php _e('Ré-engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Actif', 'tify'); ?> : </label>
        <span>
            <?php echo $subscription->isRenewEnabled() ? __('Oui', 'tify') : __('Non', 'tify') ?>
        </span>
    </dd>
    <?php if ($subscription->isRenewEnabled()) : ?>
    <dd>
        <label><?php _e('A partir de', 'tify'); ?> : </label>
        <span>
            <?php echo ($days = $subscription->getRenewDays())
                ? sprintf(_n('%d jour', '%d jours', $days, 'tify'), $days) : '--'; ?>
        </span>
    </dd>
    <dd>
        <label><?php _e('Mail de notification', 'tify'); ?> : </label>
        <span>
            <?php echo $subscription->isRenewNotify() ? __('Oui', 'tify') : __('Non', 'tify'); ?>
        </span>
    </dd>
    <?php endif; ?>
</dl>