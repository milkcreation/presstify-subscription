<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Mail;

use tiFy\Plugins\Subscription\{Order\QueryOrder, SubscriptionAwareTrait};

class Mail
{
    use SubscriptionAwareTrait;

    /**
     * Récupération de l'instance du mail de commande.
     *
     * @param int|string|QueryOrder $order
     *
     * @return OrderMail
     */
    public function order($order): ?OrderMail
    {
        $mail = $this->subscription()->resolve('mail.order');

        if (!$mail instanceof OrderMail) {
            return null;
        } elseif (!$order instanceof QueryOrder) {
            $order = $this->subscription()->order()->get($order);
        }

        return $order instanceof QueryOrder ? $mail->setOrder($order) : null;
    }
}