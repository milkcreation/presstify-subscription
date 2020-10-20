<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('subscription::layout'); ?>

<div class="SubscriptionContent SubscriptionContent--notification">
    <?php echo $this->get('content'); ?>
</div>
