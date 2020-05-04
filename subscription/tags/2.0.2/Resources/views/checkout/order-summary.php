<?php
/**
 * @var tiFy\Contracts\View\PlatesFactory $this
 * @var tiFy\Plugins\Subscription\Order\QueryOrder $order
 */
?>
<div class="OrderSummary">
    <ul>
        <?php foreach ($order->getLineItems() as $line) : ?>
            <li>
                <label><?php _e('Choix de l\'offre', 'tify'); ?></label>
                <span><?php echo $line->getLabel(); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
    <hr>
    <ul>
        <li>
            <label><?php _e('Total TTC', 'tify'); ?></label>
            <span><?php echo $order->getTotalHtml(true); ?></span>
        </li>
    </ul>
</div>