<?php
/**
 * @var tiFy\Contracts\Template\FactoryViewer $this
 */
?>
<?php echo field('datepicker', $this->get('paid_from')); ?>

<?php echo field('datepicker', $this->get('paid_to')); ?>

<?php echo field('button', $this->get('paid_filter'));