<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('subscription::layout'); ?>

<div class="SubscriptionContent SubscriptionContent--payment_success">
    <?php echo partial('flash-notice'); ?>

    <?php echo $this->get('content'); ?>
</div>