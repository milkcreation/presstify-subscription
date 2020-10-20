<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<?php $this->layout('subscription::layout'); ?>

<div class="SubscriptionContent SubscriptionContent--payment_form">
    <p><?php echo partial('flash-notice'); ?></p>

    <?php $this->insert('subscription::order-summary', $this->all()); ?>

    <?php echo $this->get('content'); ?>
</div>