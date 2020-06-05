<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use Exception;
use tiFy\Contracts\Form\FactoryField;
use tiFy\Plugins\Subscription\Order\{QueryOrder, QueryOrderLineItem};
use tiFy\Contracts\Http\{RedirectResponse, Response};
use tiFy\Routing\BaseController;
use tiFy\Support\DateTime;
use tiFy\Support\Proxy\Request;
use tiFy\Validation\Validator as v;

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
                    'Impossible de récupérer la commande.', 'tify'
                ), (string)$order_key)
            );
        } elseif (is_user_logged_in() && ($order->getCustomerId() !== $this->subscription()->customer()->getId())) {
            throw new Exception(sprintf(__(
                    '<b>Code erreur : order-unallowed--%s|%s</b><br>' .
                    'Vous n\'êtes pas autorisé à accéder à cette commande.', 'tify'
                ), (string)$order_key, 'user_' . $this->subscription()->customer()->getId())
            );
        } elseif ($check_session) {
            if (!$session_order = $this->subscription()->session()->getOrder()) {
                throw new Exception(sprintf(__(
                        '<b>Code erreur : order-missing--%s</b><br>' .
                        'Impossible d\'identifier la commande en attente de paiement.', 'tify'
                    ), (string)$order_key)
                );
            } elseif ($order->getId() !== $session_order->getId()) {
                throw new Exception(sprintf(__(
                        '<b>Code erreur : order-diff--%s|%s</b><br>' .
                        'Vous avez déjà une autre commande en attente de paiement.', 'tify'
                    ), (string)$order_key, "awaiting_{$session_order->getId()}")
                );
            }
        }

        return $order;
    }
    /**/

    /**
     * Création de la commande.
     *
     * @param array $data
     *
     * @return QueryOrder
     *
     * @throws Exception
     */
    protected function createOrder(array $data): QueryOrder
    {
        $subscr = $this->subscription();
        $session = $subscr->session();
        $gateways = $subscr->gateway()->available();

        if (!$gateway = $gateways[$data['payment_method'] ?? ''] ?? null) {
            throw new Exception(__('Méthode de paiement non trouvée ou indisponible.', 'tify'));
        }

        if ((!$order = $session->getOrder()) || $order->isStatusPaymentComplete()) {
            $order = $session->createOrder();
        }

        $order->set('status', $subscr->order()->statusDefault()->getName() ?: 'sbscodr-pending');

        if (($offer = $subscr->offer()->get($data['offer'])) && $offer->isPurchasable()) {
            $qty = 1;

            $order->createLineItem([
                'name'           => $offer->getName(),
                'label'          => $offer->getLabel(),
                'limited'        => $offer->isLimitedEnabled() ? 'on' : 'off',
                'limited_length' => $offer->getLimitedLength(),
                'limited_unity'  => $offer->getLimitedUnity(),
                'order_id'       => $order->getId(),
                'price'          => $offer->getPrice(),
                'offer_id'       => $offer->getId(),
                'quantity'       => $qty,
                'sku'            => $offer->getSku(),
                'subtotal'       => $offer->getPriceWithTax($qty),
                'subtotal_tax'   => $offer->getPriceTax($qty),
                'renewable'      => $offer->isRenewEnabled() ? 'on' : 'off',
                'renew_days'     => $offer->getRenewDays(),
                'renew_notify'   => $offer->isRenewNotify(),
                'total'          => $offer->getPriceWithTax($qty),
                'total_tax'      => $offer->getPriceTax($qty),
                'type'           => 'offer',
            ]);
        } else {
            throw new Exception(__('Aucune offre trouvée, ou l\'offre choisie n\'est plus disponible.', 'tify'));
        }

        // - Association des données de transaction.
        $email = $data['billing_email'] ?? '';

        $order->set([
            'created_via'          => 'checkout',
            'currency'             => $subscr->settings()->getCurrency(),
            'customer_id'          => $subscr->customer(get_current_user_id() ?: $email)->getId(),
            'customer_email'       => $email,
            'customer_ip_address'  => Request::ip(),
            'customer_user_agent'  => Request::header('User-Agent'),
            'payment_method'       => $gateway->getName(),
            'payment_method_title' => $gateway->getLabel(),
            'prices_include_tax'   => $subscr->settings()->isPricesIncludeTax(),
            'total'                => $order->calculateTotal(),
            'total_tax'            => $order->calculateTotalTax(),
        ]);

        // - Association des données d'abonnement.
        foreach ($data as $key => $value) {
            $order->set("subscription_form.{$key}", $value);
        }

        // - Association des données de facturation et de livraison.
        $data['shipping_as_billing'] = isset($data['shipping_as_billing'])
            ? filter_var($data['shipping_as_billing'], FILTER_VALIDATE_BOOLEAN) : false;

        if ($data['shipping_as_billing']) {
            foreach ($data as $key => $value) {
                if (preg_match('/^billing_(.*)$/', $key, $match)) {
                    $data["shipping_{$match[1]}"] = $value;
                }
            }
        }

        foreach ($data as $key => $value) {
            if (preg_match('/^(billing|shipping)_(.*)$/', $key, $match)) {
                $order->set("{$match[1]}.{$match[2]}", $value);
            }
        }

        $order->update();

        $session->save();

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
        $subscr = $this->subscription();

        if (($order = $subscr->order()->get($order_key)) && !$order->isStatusPaymentComplete()) {
            if ($order->isNeedShipping()) {
                $order->set('status', 'processing');
            } else {
                $order->set('status', 'completed');
                $order->set('date_completed', (new DateTime())->utc('U'));
            }

            $order->update();

            if (!$line = $order->getLineItems()[0] ?: null) {
                $message = __('La commande n\'a aucune offre d\'abonnement à créer.', 'tify');
                $order->addNote($message);
                $this->subscription()->log()->info($message, ['order' => $order->all()]);
            } elseif (!$subscription = $order->getSubscription()) {
                try {
                    $subscription = $line->createSubscription($this->getSubscriptionData($line));
                } catch (Exception $e) {
                    $message = __('Impossible de créer un nouvel abonnement.', 'tify');
                    $order->addNote($message);
                    $this->subscription()->log()->error($message, ['order' => $order->all()]);
                }
            }

            if (isset($subscription)) {
                $order->set('subscription_id', $subscription->getId())->update();

                $message = sprintf(__(
                    'L\'abonnement n°[#%d] a été créé et associé à la commande.', 'tify'), $subscription->getId()
                );
                $order->addNote($message);
                $this->subscription()->log()->success($message, [
                    'order'        => $order->all(),
                    'subscription' => $subscription->all(),
                ]);
            }

            $order->getMail()->send();
        }
    }
    /**/

    /**
     * Données de création de l'abonnement.
     *
     * @param QueryOrderLineItem $line
     *
     * @return array
     */
    protected function getSubscriptionData(QueryOrderLineItem $line): array
    {
        return [];
    }
    /**/

    /**
     * Traitement de l'annulation de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return RedirectResponse
     */
    public function handleCancelled(string $order_key): RedirectResponse
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key, false));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'tify'));

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
     * @return RedirectResponse
     */
    public function handleFailed(string $order_key): RedirectResponse
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key, false));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'tify'));

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
            $this->subscription()->log()->error(sprintf(
                __('Notification de paiement instantané en échec de la commande [%s] >> [%s]', 'tify'),
                $order_key,
                __('Commande indisponible.', 'tify')
            ));

            return $this->response('KO', 400);
        } elseif (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->log()->error(sprintf(
                __('Notification de paiement instantané en échec de la commande [%s] >> [%s]', 'tify'),
                $order_key,
                __('Plateforme de paiement indisponible.', 'tify')
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
     * @return RedirectResponse
     *
     * @todo TESTER
     */
    public function handleOnHold(string $order_key): RedirectResponse
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key, false));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'tify'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handleOnHold();

        $order->updateStatus('on-hold');

        $this->subscription()->session()->destroy();

        return $this->subscription()->route('payment-success')->redirect([$order_key]);
    }
    /**/

    /**
     * Traitement du succès de paiement.
     *
     * @param string $order_key Clé d'identification de la commande.
     *
     * @return RedirectResponse
     */
    public function handleSuccessed(string $order_key): RedirectResponse
    {
        try {
            $this->set('order', $order = $this->checkOrder($order_key, false));
        } catch (Exception $e) {
            $this->subscription()->notify($e->getMessage());

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'tify'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        $gateway->handleSuccessed();

        $this->completeOrder($order_key);

        $this->subscription()->session()->destroy();

        return $this->subscription()->route('payment-success')->redirect([$order_key]);
    }
    /**/

    /**
     * Formulaire de commande de l'abonnement.
     *
     * @return Response
     */
    public function orderForm(): Response
    {
        $form = $this->subscription()->form()->prepare();

        if (Request::isMethod('post')) {
            if ($data = $this->orderFormValidate($form)) {
                try {
                    $order = $this->createOrder($data);

                    return $this->subscription()->route('payment-form')->redirect([$order->getOrderKey()]);
                } catch (Exception $e) {
                    $form->error($e->getMessage());
                }
            }
        }

        $this->set(compact('form'));

        return $this->viewOrderForm($this->all());
    }
    /**/

    /**
     * Validation du formulaire de commande de l'abonnement
     *
     * @param SubscriptionOrderForm $form
     *
     * @return array
     */
    public function orderFormValidate(SubscriptionOrderForm $form): array
    {
        $data = [];

        $form->request()->prepare();

        /** @var FactoryField[] $fields */
        $fields = $form->fields()->all();

        if (!$form->request()->verify()) {
            $form->error(__('Une erreur est survenue, impossible de valider votre demande de contact.', 'tify'));
        } else {
            if (!v::notEmpty()->validate($form->request()->get('billing_lastname'))) {
                $fields['billing_lastname']->addError(__('Veuillez renseigner votre nom de famille.', 'tify'));
            }

            if (!v::notEmpty()->validate($form->request()->get('billing_firstname'))) {
                $fields['billing_firstname']->addError(__('Veuillez renseigner votre prénom.', 'tify'));
            }

            if (!v::notEmpty()->validate($form->request()->get('billing_address1'))) {
                $fields['billing_address1']->addError(__('Veuillez renseigner votre adresse postale.', 'tify'));
            }

            if (!v::notEmpty()->validate($form->request()->get('billing_postcode'))) {
                $fields['billing_postcode']->addError(__('Veuillez renseigner votre code postal.', 'tify'));
            }

            if (!v::notEmpty()->validate($form->request()->get('billing_city'))) {
                $fields['billing_city']->addError(__('Veuillez renseigner votre ville.', 'tify'));
            }

            if (!v::notEmpty()->validate($form->request()->get('billing_phone'))) {
                $fields['billing_phone']->addError(__('Veuillez renseigner votre numéro de téléphone.', 'tify'));
            }

            $email = $form->request()->get('billing_email');
            if (!v::notEmpty()->validate($email)) {
                $fields['billing_email']->addError(__('Veuillez renseigner votre adresse de messagerie.', 'tify'));
            } elseif (!v::email()->validate($email)) {
                $fields['billing_email']->addError(
                    __('L\'adresse de messagerie renseignée n\'est pas un e-mail valide.', 'tify')
                );
            }

            if (!v::notEmpty()->validate($form->request()->get('offer'))) {
                $fields['offer']->addError(__('Veuillez choisir votre formule d\'abonnement.', 'tify'));
            }

            foreach ($fields as $slug => $field) {
                if ($field->supports('transport')) {
                    $field->setValue($form->request()->get($slug));
                }
            }

            if (!$form->hasError()) {
                $customer = $this->subscription()->customer(get_current_user_id() ?: $email);

                if (!$customer->canSubscribe()) {
                    $form->error(sprintf(
                        __('Une autre souscription associée à [%s] existe déjà pour la période en cours.', 'theme'),
                        is_user_logged_in()
                            ? wp_get_current_user()->display_name . '/' . wp_get_current_user()->user_email
                            : $email
                    ));
                }
            }

            if (!$form->hasError()) {
                $data = $form->request()->all();
            }
        }

        return $data;
    }
    /**/

    /**
     * Renouvellement de la commande.
     *
     * @return RedirectResponse
     */
    public function orderRenew(): RedirectResponse
    {
        $this->subscription()->session()->destroy();

        return $this->subscription()->route('order-form')->redirect();
    }

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

        return $this->viewPaymentError($this->all());
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

        if (!$order->getCustomer()->canSubscribe()) {
            $this->subscription()->notify(sprintf(
                __('Une autre souscription associée à [%s] existe déjà pour la période en cours.', 'theme'),
                is_user_logged_in()
                    ? wp_get_current_user()->display_name . '/' . wp_get_current_user()->user_email
                    : $order->getCustomerEmail()
            ));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        }

        if (!$gateway = $order->getPaymentGateway()) {
            $this->subscription()->notify(__('La plateforme de paiement est indisponible', 'tify'));

            return $this->subscription()->route('payment-error')->redirect([$order_key]);
        } else {
            $this->set('form', $order->getPaymentGateway()->getPaymentForm());
        }

        return $this->viewPaymentForm($this->all());
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

        return $this->viewPaymentSuccess($this->all());
    }
    /**/

    /**
     * Affichage du gabarit de formulaire d'abonnement.
     *
     * @param array $data
     *
     * @return Response
     */
    public function viewOrderForm(array $data): Response
    {
        return $this->view('subscription::order-form', $data);
    }
    /**/

    /**
     * Affichage du gabarit des erreurs de paiement.
     *
     * @param array $data
     *
     * @return Response
     */
    public function viewPaymentError(array $data): Response
    {
        return $this->view('subscription::payment-error', $data);
    }
    /**/

    /**
     * Affichage du gabarit du formulaire de paiement.
     *
     * @param array $data
     *
     * @return Response
     */
    public function viewPaymentForm(array $data): Response
    {
        return $this->view('subscription::payment-form', $data);
    }
    /**/

    /**
     * Affichage du gabarit de succès du formulaire de paiement.
     *
     * @param array $data
     *
     * @return Response
     */
    public function viewPaymentSuccess(array $data): Response
    {
        return $this->view('subscription::payment-success', $data);
    }
    /**/
}