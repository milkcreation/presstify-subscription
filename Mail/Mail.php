<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Mail;

use tiFy\Plugins\Subscription\{Order\QueryOrder, QuerySubscription, SubscriptionAwareTrait};

class Mail
{
    use SubscriptionAwareTrait;

    /**
     * Récupération de l'instance du mail de confirmation d'abonnement.
     *
     * @param int|string|QueryOrder $order
     *
     * @return OrderConfirmationMail
     */
    public function orderConfirmation($order): ?OrderConfirmationMail
    {
        $mail = $this->subscription()->resolve('mail.order-confirmation');

        if (!$mail instanceof OrderConfirmationMail) {
            return null;
        } elseif (!$order instanceof QueryOrder) {
            $order = $this->subscription()->order()->get($order);
        }

        return $order instanceof QueryOrder ? $mail->setOrder($order) : null;
    }

    /**
     * Récupération de l'instance du mail de notification d'abonnement.
     *
     * @param int|string|QueryOrder $order
     *
     * @return OrderNotificationMail
     */
    public function orderNotification($order): ?OrderNotificationMail
    {
        $mail = $this->subscription()->resolve('mail.order-notification');

        if (!$mail instanceof OrderNotificationMail) {
            return null;
        } elseif (!$order instanceof QueryOrder) {
            $order = $this->subscription()->order()->get($order);
        }

        return $order instanceof QueryOrder ? $mail->setOrder($order) : null;
    }

    /**
     * Récupération de l'instance du mail d'invite de ré-engagement.
     *
     * @param int|string|QuerySubscription $subscription
     *
     * @return RenewNotifyMail
     */
    public function renewNotify($subscription): ?RenewNotifyMail
    {
        $mail = $this->subscription()->resolve('mail.renew-notify');

        if (!$mail instanceof RenewNotifyMail) {
            return null;
        } elseif (!$subscription instanceof QuerySubscription) {
            $subscription = $this->subscription()->get($subscription);
        }

        return $subscription instanceof QuerySubscription ? $mail->setObj($subscription) : null;
    }
}