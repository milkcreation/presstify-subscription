<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Form\FormFactory as BaseFormFactory;

class SubscriptionOrderForm extends BaseFormFactory
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur de traitement automatique.
     * @var boolean|null
     */
    protected $auto = false;

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        // - Méthode de paiement disponibles.
        $gateway_choices = [];
        $payment_method = [];

        if ($gateways = $this->subscription()->gateway()->available()) {
            if (count($gateways) > 1) {
                foreach ($gateways as $gateway) {
                    $gateway_choices[$gateway->getName()] = $gateway->getLabel();
                }

                $payment_method = [
                    'title'   => __('Choix de la plateforme de paiement', 'tify'),
                    'type'    => 'radio-collection',
                    'choices' => $gateway_choices,
                ];
            } else {
                $payment_method = [
                    'type'  => 'hidden',
                    'value' => reset($gateways)->getName(),
                ];
            }
        }

        // - Liste des offres disponibles.
        $offer_choices = [];
        if ($offers = $this->subscription()->offer()->fetch([
            'post_status' => 'publish',
            'orderby'     => ['menu_order' => 'ASC'],
        ])) {
            foreach ($offers as $offer) {
                $offer_choices[$offer->getName()] = $offer->getLabel();
            }
        }

        $this->set([
            'fields' => [
                'payment_method'     => $payment_method,
                'offer'              => [
                    'title'   => __('Choix de l\'offre', 'tify'),
                    'type'    => 'radio-collection',
                    'choices' => $offer_choices,
                ],
                'billing-title'      => [
                    'type'  => 'html',
                    'value' => '<h3>' . __('Adresse de facturation', 'tify') . '</h3>',
                ],
                'billing_lastname'   => [
                    'required' => true,
                    'title'    => __('Nom de famille', 'tify'),
                    'type'     => 'text',
                ],
                'billing_firstname'  => [
                    'required' => true,
                    'title'    => __('Prénom', 'tify'),
                    'type'     => 'text',
                ],
                'billing_address1'   => [
                    'required' => true,
                    'title'    => __('Adresse postale', 'tify'),
                    'type'     => 'text',
                ],
                'billing_address2'   => [
                    'title' => __('Complément d\'adresse', 'tify'),
                    'type'  => 'text',
                ],
                'billing_postcode'   => [
                    'required' => true,
                    'title'    => __('Code postal', 'tify'),
                    'type'     => 'text',
                ],
                'billing_city'       => [
                    'required' => true,
                    'title'    => __('Ville', 'tify'),
                    'type'     => 'text'
                ],
                'billing_phone'      => [
                    'required' => true,
                    'title'    => __('Numéro de téléphone', 'tify'),
                    'type'     => 'text'
                ],
                'billing_email'      => [
                    'required' => true,
                    'title'    => __('Adresse de messagerie', 'tify'),
                    'type'     => 'text'
                ],
                'shipping-title'     => [
                    'type'  => 'html',
                    'value' => '<h3>' . __('Adresse de livraison', 'tify') . '</h3>',
                ],
                'shipping_lastname'  => [
                    'required' => true,
                    'group'    => 'shipping',
                    'title'    => __('Nom de famille', 'tify'),
                    'type'     => 'text'
                ],
                'shipping_firstname' => [
                    'required' => true,
                    'group'    => 'shipping',
                    'title'    => __('Prénom', 'tify'),
                    'type'     => 'text'
                ],
                'shipping_address1'  => [
                    'required' => true,
                    'group'    => 'shipping',
                    'title'    => __('Adresse postale', 'tify'),
                    'type'     => 'text'
                ],
                'shipping_address2'  => [
                    'group' => 'shipping',
                    'title' => __('Complément d\'adresse', 'tify'),
                    'type'  => 'text'
                ],
                'shipping_postcode'  => [
                    'required' => true,
                    'group'    => 'shipping',
                    'title'    => __('Code postal', 'tify'),
                    'type'     => 'text'
                ],
                'shipping_city'      => [
                    'required' => true,
                    'group'    => 'shipping',
                    'title'    => __('Ville', 'tify'),
                    'type'     => 'text'
                ],
                'legend'             => [
                    'group'   => 'submit',
                    'label'   => false,
                    'type'    => 'html',
                    'value'   => __('* Champs obligatoires', 'tify'),
                    'wrapper' => true,
                ],
                'submit'             => [
                    'extras' => [
                        'content' => __('Passer commande', 'tify'),
                        'type'    => 'submit',
                    ],
                    'group'  => 'submit',
                    'type'   => 'button',
                ],
            ],
        ]);
    }
}