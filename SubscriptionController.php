<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use Exception;
use tiFy\Plugins\Subscription\Order\QueryOrder;
use tiFy\Contracts\Http\Response;
use tiFy\Routing\BaseController;
use tiFy\Support\DateTime;
//use tiFy\Support\Proxy\Request;

class SubscriptionController extends BaseController
{
    use SubscriptionAwareTrait;

    /**
     * Vérification de la commande.
     *
     * @param string $order_key Clé d'identification de la commande.
     * @param bool $check_session Vérification liée à la commande enregistrée en session.
     *
     * @return QueryOrder
     *
     * @throws Exception
     */
    protected function checkOrder(string $order_key, bool $check_session = true): QueryOrder
    {
        if (!$order = $this->subscription()->order()->get($order_key)) {
            throw new Exception(sprintf(__(
                    '<b>Code erreur : order-unavailable--%s</b><br>' .
                    'Impossible de récupérer la commande.', 'theme'
                ), (string)$order_key)
            );
        } elseif ($order->getCustomerId() !== $this->subscription()->user()->getId()) {
            throw new Exception(sprintf(__(
                    '<b>Code erreur : order-unallowed--%s|%s</b><br>' .
                    'Vous n\'êtes pas autorisé à accéder à cette commande.', 'theme'
                ), (string)$order_key, 'user_' . $this->subscription()->user()->getId())
            );
        } elseif ($check_session) {
            if (!$session_order = $this->subscription()->session()->getOrder()) {
                throw new Exception(sprintf(__(
                        '<b>Code erreur : order-missing--%s</b><br>' .
                        'Impossible d\'identifier la commande en attente de paiement.', 'theme'
                    ), (string)$order_key)
                );
            } elseif ($order->getId() !== $session_order->getId()) {
                throw new Exception(sprintf(__(
                        '<b>Code erreur : order-diff--%s|%s</b><br>' .
                        'Vous avez déjà une autre commande en attente de paiement.', 'theme'
                    ), (string)$order_key, "awaiting_{$session_order->getId()}")
                );
            }
        }

        return $order;
    }
    /**/

    /**
     * Finalisation de la commande.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return void
     */
    protected function completeOrder(string $order_key): void
    {
        if (($order = $this->subscription()->order()->get($order_key)) && !$order->isStatusPaymentComplete()) {
            try {
                $subscr = $order->createSubscription();

                $message = sprintf(__(
                    'L\'abonnement [#%d] a été créé et associé à la commande.', 'theme'
                ), $subscr->getId());
                $order->addNote($message);
                $this->subscription()->log()->addSuccess($message, [
                    'order'        => $order->all(),
                    'subscription' => $subscr->all()
                ]);

                if ($order->isNeedShipping()) {
                    $order->set('status', 'processing');
                } else {
                    $order->set('status', 'completed');
                    $order->set('date_completed', (new DateTime())->utc('U'));
                }

                $order->update();
            } catch (Exception $e) {
                $subscr = null;

                switch ($e->getMessage()) {
                    default :
                        $message = __('Le processus de création de l\'abonnement n\'a pu aboutir.', 'tify');
                        $order->addNote($message);
                        $this->subscription()->log()->addError($message, ['order' => $order->all()]);
                        break;
                }
            }

            $order->getMail()->send();
        }
    }
    /**/

    /**
     * Traitement de l'annulation de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function handleCancelled(string $order_key): Response
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'theme'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handleCancelled();

        return $this->subscription()->route('payment-form')->redirect([$order_key]);
    }
    /**/

    /**
     * Traitement de l'échec de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function handleFailed(string $order_key): Response
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'theme'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handleFailed();

        return $this->subscription()->route('payment-form')->redirect([$order_key]);
    }
    /**/

    /**
     * Traitement de la notification de paiement instantané.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function handleIpn(string $order_key): Response
    {
        if (!$order = $this->subscription()->order()->get($order_key)) {
            $this->subscription()->log()->addError(sprintf(
                __('Notification de paiement instantané en échec de la commande [%s] >> [%s]', 'theme'),
                $order_key,
                __('Commande indisponible.', 'theme')
            ));

            return $this->response('KO', 400);
        } elseif (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->log()->addError(sprintf(
                __('Notification de paiement instantané en échec de la commande [%s] >> [%s]', 'theme'),
                $order_key,
                __('Plateforme de paiement indisponible.', 'theme')
            ));

            return $this->response('KO', 400);
        }

        $gateway->handleIpn();

        $this->completeOrder($order_key);

        return $this->response('OK', 200);
    }
    /**/

    /**
     * Traitement d'un paiement en attente de réglement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     *
     * @todo
     */
    public function handlePending(string $order_key): Response
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'theme'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handlePending();

        $order->updateStatus('pending');

        $this->subscription()->session()->destroy();

        return $this->subscription()->route('payment-success')->redirect([$order_key]);
    }
    /**/

    /**
     * Traitement du succès de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function handleSuccessed(string $order_key): Response
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'theme'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handleSuccessed();

        $this->completeOrder($order_key);

        $this->subscription()->session()->destroy();

        return $this->subscription()->route('payment-success')->redirect([$order_key]);
    }
    /**/

    /**
     * Commande.
     * @todo
     * /
    public function order()
    {
        $subscr = $this->subscription();
        $session = $subscr->session();

        if (!$order = $session->getOrder()) {
            $order = $session->createOrder();
        }

        $order->set('status', $subscr->order()->statusDefault()->getName() ?: 'sbscodr-pending');

        if (($offer = $subscr->offer()->get(Request::input('membership-offer'))) && $offer->isPurchasable()) {
            $qty = 1;

            $order->createLineItem([
                'duration_length'    => $offer->getDurationLength(),
                'duration_unity'     => $offer->getDurationUnity(),
                'name'               => $offer->getName(),
                'label'              => $offer->getLabel(),
                'order_id'           => $order->getId(),
                'price'              => $offer->getPrice(),
                'offer_id'           => $offer->getId(),
                'quantity'           => $qty,
                'sku'                => $offer->getSku(),
                'subtotal'           => $offer->getPriceWithTax($qty),
                'subtotal_tax'       => $offer->getPriceTax($qty),
                'renewable_days'     => $offer->getRenewableDays(),
                'renew_notification' => $offer->isRenewNotify(),
                'total'              => $offer->getPriceWithTax($qty),
                'total_tax'          => $offer->getPriceTax($qty),
                'type'               => 'offer',
            ]);
        } else {
            $form->error(__('Aucune offre trouvée, ou l\'offre choisie n\'est plus disponible.', 'theme'));
        }

        $order->set([
            'created_via'          => 'checkout',
            'customer_id'          => $this->app->user()->getId(),
            'currency'             => $subscr->settings()->getCurrency(),
            'prices_include_tax'   => $subscr->settings()->isPricesIncludeTax(),
            'customer_ip_address'  => Request::ip(),
            'customer_user_agent'  => Request::header('User-Agent'),
            'payment_method'       => 'etransactions',
            'payment_method_title' => __('Carte bancaire', 'theme'),
            'total'                => $order->calculateTotal(),
            'total_tax'            => $order->calculateTotalTax(),
        ]);

        // -- Données de facturation.
        $keys = ['civility', 'lastname', 'firstname', 'address', 'postcode', 'city', 'phone', 'gsm', 'email'];
        foreach($keys as $key) {
            if ($field = $form->field($key)) {
                $order->set("billing.{$key}", $field->getValue());
            }
        }

        // -- Données d'adhésion.
        foreach ($fields as $slug => $field) {
            if ($field->supports('transport')) {
                $order->set('subscription_form.' . $field->getSlug(), $field->getValue());
            }
        }

        $order->update();

        $session->save();
    }
    /**/

    /**
     * Affichage des erreurs de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function paymentError(string $order_key): Response
    {
        if ($order = $this->subscription()->order()->get($order_key)) {
            $this->set(compact('order'));
        }

        return $this->view('subscription::payment-error', $this->all());
    }
    /**/

    /**
     * Affichage du formulaire de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function paymentForm(string $order_key): Response
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'theme'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        } else {
            $this->set('form', $order->getPaymentGateway()->getPaymentForm());
        }

        return $this->view('subscription::payment-form', $this->all());
    }
    /**/

    /**
     * Affichage du succès de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return Response
     */
    public function paymentSuccess(string $order_key)
    {
        if ($order = $this->subscription()->order()->get($order_key)) {
            $this->set(compact('order'));
        }

        return $this->view('subscription::payment-success', $this->all());
    }
}