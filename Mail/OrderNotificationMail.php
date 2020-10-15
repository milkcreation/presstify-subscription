<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Mail;

use tiFy\Plugins\Subscription\Order\QueryOrder;
use tiFy\Mail\Mail as BaseMail;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;

class OrderNotificationMail extends BaseMail
{
    use SubscriptionAwareTrait;

    /**
     * Instance de la commande associée.
     * @var QueryOrder|null
     */
    protected $order;

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return array_merge(parent::defaults(), [
            'data'    => $this->order->getInvoiceDatas(),
            'subject' => sprintf(
                __('[%s] >> Nouvelle commande : %s', 'tify'),
                get_bloginfo('blogname'), $this->order->getNumber() ?: $this->order->getId()
            ),
            'from'    => $this->subscription()->settings()->getOrderNotificationSender(),
            'to'      => $this->subscription()->settings()->getOrderNotificationRecipients(),
            'viewer'  => [
                'override_dir' => $this->subscription()->resources('/views/mail/order-notification'),
            ],
        ]);
    }

    /**
     * Définition de l'instance de la commande associée.
     *
     * @param QueryOrder $order
     *
     * @return $this
     */
    public function setOrder(QueryOrder $order): self
    {
        $this->order = $order;

        return $this;
    }
}