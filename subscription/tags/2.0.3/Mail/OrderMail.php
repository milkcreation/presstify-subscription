<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Mail;

use tiFy\Plugins\Subscription\Order\QueryOrder;
use tiFy\Mail\Mail as BaseMail;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;

class OrderMail extends BaseMail
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
                __('[%s] >> Votre commande n°%d', 'tify'), get_bloginfo('blogname'), $this->order->getId()
            ),
            'to'      => $this->order->getBilling('email'),
            'viewer'  => [
                'override_dir' => $this->subscription()->resources('/views/mail/order'),
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