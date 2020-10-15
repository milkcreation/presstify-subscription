<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('subscription::layout'); ?>

<div class="SubscriptionContent SubscriptionContent--payment_error">
    <?php echo partial('flash-notice'); ?>

    <?php echo $this->get('content'); ?>
</div>
