<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<?php $this->layout('subscription::layout'); ?>

<p><?php echo partial('flash-notice'); ?></p>

<div class="CheckoutSummary">
    <ul>
    <?php foreach ($order->getLineItems() as $line) : ?>
        <li>
            <label><?php _e('Choix de l\'offre', 'theme'); ?></label>
            <span><?php echo $line->getLabel(); ?></span>
        </li>
    <?php endforeach; ?>
    </ul>
    <hr>
    <ul>
        <li>
            <label><?php _e('Total TTC', 'theme'); ?></label>
            <span><?php echo $order->getTotalHtml(true); ?></span>
        </li>
    </ul>
</div>
<div class="CheckoutForm">
    <?php echo $this->get('form'); ?>
</div>