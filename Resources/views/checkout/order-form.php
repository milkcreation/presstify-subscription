<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 */
?>
<?php $this->layout('subscription::layout'); ?>

<p><?php echo partial('flash-notice'); ?></p>

<?php echo $this->get('form');