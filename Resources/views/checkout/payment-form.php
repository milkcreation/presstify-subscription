<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<?php $this->layout('subscription::layout'); ?>

<p><?php echo partial('flash-notice'); ?></p>

<div class="PaymentForm">
    <?php $this->insert('subscription::order-summary', $this->all()); ?>

    <?php echo $this->get('form'); ?>
</div>