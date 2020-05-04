<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\Order;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Plugins\Transaction\Wordpress\Template\ExportListTableWpPost\Factory as BaseFactory;

class ListTableFactory extends BaseFactory
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        $format = [
            $this->subscription()->settings()->getPriceDecimals(),
            $this->subscription()->settings()->getPriceDecimalSeparator(),
            $this->subscription()->settings()->getPriceThousandSeparator(),
        ];

        $this->set([
            'labels'    => [
                'plural'     => __('Exports de commandes', 'tify'),
                'singular'   => __('Export de commande', 'tify'),
                'page_title' => __('Export des commandes', 'tify'),
            ],
            'params'    => [
                'ajax'         => false,
                'columns'      => [
                    'id'                 => [
                        'content' => function (Item $order) {
                            return $order->getId();
                        },
                        'title'   => __('N°', 'tify'),
                    ],
                    'customer'           => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()->getUser())
                                ? sprintf('%s (#%d - %s)', $u->getDisplayName(), $u->getId(), $u->getEmail())
                                : ($order->getCustomer()->getEmail() ?: '--');
                        },
                        'title'   => __('Client', 'tify'),
                    ],
                    'lastname'           => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()->getUser())
                                ? ($u->getLastName() ?: __('n.r.', 'tify')) : '--';
                        },
                        'title'   => __('Nom', 'tify'),
                    ],
                    'firstname'          => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()->getUser())
                                ? ($u->getFirstName() ?: __('n.r.', 'tify')) : '--';
                        },
                        'title'   => __('Prénom', 'tify'),
                    ],
                    /*'company_name'       => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()) ? ($u->getCompanyName() ?: __('n.r.', 'tify')) : '--';
                        },
                        'title'   => __('Société', 'tify'),
                    ],
                    'company_status'     => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer())
                                ? ($u->getCompanyStatus() ?: __('n.r.', 'tify')) : '--';
                        },
                        'title'   => __('Forme juridique', 'tify'),
                    ],*/
                    'transaction_id'     => [
                        'content' => function (Item $order) {
                            return $order->getTransactionId() ?: '--';
                        },
                        'title'   => __('Numéro de trans.', 'tify'),
                    ],
                    'payment_method'     => [
                        'content' => function (Item $order) {
                            return $order->getPaymentMethodTitle() ?: '--';
                        },
                        'title'   => __('Moyen de paiement', 'tify'),
                    ],
                    'payment_date'       => [
                        'content' => function (Item $order) {
                            return ($date = $order->getPaymentDatetime()) ? $date->format('d/m/Y H:i:s') : '--';
                        },
                        'title'   => __('Date de réglement', 'tify'),
                    ],
                    'total_with_tax'     => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalWithTax(), ...$format);
                        },
                        'title'   => __('Montant TTC', 'tify'),
                    ],
                    'total_without_tax'  => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalWithoutTax(), ...$format);
                        },
                        'title'   => __('Montant HT', 'tify'),
                    ],
                    'tax'                => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalTax(), ...$format);
                        },
                        'title'   => __('Taxe', 'tify'),
                    ],
                    'billing_lastname'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('lastname');
                        },
                        'title'   => __('Factu. Nom', 'tify'),
                    ],
                    'billing_firstname'  => [
                        'content' => function (Item $order) {
                            return $order->getBilling('firstname');
                        },
                        'title'   => __('Factu. Prénom', 'tify'),
                    ],
                    'billing_company'    => [
                        'content' => function (Item $order) {
                            return $order->getBilling('company');
                        },
                        'title'   => __('Factu. Société', 'tify'),
                    ],
                    'billing_address1'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('address1');
                        },
                        'title'   => __('Factu. Adresse', 'tify'),
                    ],
                    'billing_address2'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('address2');
                        },
                        'title'   => __('Factu. Comp. Adresse', 'tify'),
                    ],
                    'billing_postcode'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('postcode');
                        },
                        'title'   => __('Factu. C.P.', 'tify'),
                    ],
                    'billing_city'       => [
                        'content' => function (Item $order) {
                            return $order->getBilling('city');
                        },
                        'title'   => __('Factu. Ville', 'tify'),
                    ],
                    'billing_phone'      => [
                        'content' => function (Item $order) {
                            return $order->getBilling('phone');
                        },
                        'title'   => __('Factu. Tél', 'tify'),
                    ],
                    'billing_email'      => [
                        'content' => function (Item $order) {
                            return $order->getBilling('email');
                        },
                        'title'   => __('Factu. Email', 'tify'),
                    ],
                    'shipping_lastname'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('lastname');
                        },
                        'title'   => __('Liv. Nom', 'tify'),
                    ],
                    'shipping_firstname' => [
                        'content' => function (Item $order) {
                            return $order->getShipping('firstname');
                        },
                        'title'   => __('Liv. Prénom', 'tify'),
                    ],
                    'shipping_company'   => [
                        'content' => function (Item $order) {
                            return $order->getShipping('company');
                        },
                        'title'   => __('Liv. Société', 'tify'),
                    ],
                    'shipping_address1'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('address1');
                        },
                        'title'   => __('Liv. Adresse', 'tify'),
                    ],
                    'shipping_address2'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('address2');
                        },
                        'title'   => __('Liv. Comp. Adresse', 'tify'),
                    ],
                    'shipping_postcode'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('postcode');
                        },
                        'title'   => __('Liv. C.P.', 'tify'),
                    ],
                    'shipping_city'      => [
                        'content' => function (Item $order) {
                            return $order->getShipping('city');
                        },
                        'title'   => __('Liv. Ville', 'tify'),
                    ],
                ],
                'query_args'   => [
                    'order'    => 'DESC',
                    'per_page' => 20,
                ],
                'bulk-actions' => false,
                'row-actions'  => false,
                'search'       => false,
                'view-filters' => false,
                'table'        => [
                    'before' => '<div class="TableResponsive">',
                    'after'  => '</div>',
                ],
                'wordpress'    => [
                    'admin_menu' => [
                        'parent_slug' => 'subscription',
                        'position'    => 3,
                    ],
                ],
            ],
            'providers' => [
                'db'   => (new Db())->setSubscription($this->subscription),
                'item' => (new Item())->setSubscription($this->subscription),
            ],
        ]);
    }
}
