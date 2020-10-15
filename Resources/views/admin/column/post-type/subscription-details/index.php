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
            <label><?php _e('Durée d\'abonnement', 'tify'); ?> : </label>
            <span>
            <?php echo $subscription->getLimitedHtml() ?: '--' ?>
        </span>
        </dd>
        <dd>
            <label><?php _e('Début d\'abonnement', 'tify'); ?> : </label>
            <span>
            <?php echo ($date = $subscription->getStartDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
        </span>
        </dd>
        <dd>
            <label><?php _e('Fin d\'abonnement', 'tify'); ?> : </label>
            <span>
           <?php echo ($date = $subscription->getEndDate()) ? $date->format('d/m/Y H\hi') : '--'; ?>
        </span>
        </dd>
    <?php endif; ?>

    <dt><?php _e('Ré-engagement', 'tify'); ?></dt>
    <dd>
        <label><?php _e('Actif', 'tify'); ?> : </label>
        <span><?php $subscription->isRenewEnabled() ? printf(
                _nx('%d jour avant', '%d jours avant expiration', $subscription->getRenewDays(), 'tify'),
                $subscription->getRenewDays()
            ) : _e('Non', 'tify'); ?></span>
    </dd>
    <?php if ($subscription->isRenewEnabled()) : ?>
        <hr>
        <dd>
            <label><?php _e('Mail de rappel', 'tify'); ?> : </label>
            <span><?php echo $subscription->isRenewNotifyEnabled() ? __('Oui', 'tify') : __('Non', 'tify'); ?></span>
        </dd>

        <?php if ($date = $subscription->getRenewNotified()) : ?>
            <dd>
                <label><?php _e('Envoyé le', 'tify'); ?> : </label>
                <span><?php echo $date->format('d/m/Y à H\hi'); ?></span>
            </dd>
        <?php elseif ($subscription->isRenewNotifyEnabled() && ($days = $subscription->getRenewNotifyDays()) && !$subscription->isExpired()) : ?>
            <dd>
                <label><?php _e('Programmé le', 'tify'); ?> : </label>
                <span><?php printf(_n(
                    '%s (%d jour avant expiration)', '%s (%d jours avant expiration)', $days, 'tify'
                ), $subscription->getRenewNotifyDate()->format('d/m/Y'), $days); ?></span>
            </dd>
        <?php endif; ?>
    <?php endif; ?>
</dl>