<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('subscription::layout'); ?>

<div class="SubscriptionContent SubscriptionContent--form">
    <p><?php echo partial('flash-notice'); ?></p>

    <?php echo $this->get('content'); ?>
</div>