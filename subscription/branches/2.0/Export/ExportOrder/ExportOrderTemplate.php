<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\ExportOrder;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Plugins\Transaction\Wordpress\Template\ExportListTableWpPost\Factory as BaseFactory;

class ExportOrderTemplate extends BaseFactory
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
                'plural'     => __('Exports de commandes', 'theme'),
                'singular'   => __('Export de commande', 'theme'),
                'page_title' => __('Export des commandes', 'theme'),
            ],
            'params'    => [
                'ajax'         => false,
                'columns'      => [
                    'id'                 => [
                        'content' => function (Item $order) {
                            return $order->getId();
                        },
                        'title'   => __('N°', 'theme'),
                    ],
                    'customer'           => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer())
                                ? sprintf('%s (#%d - %s)', $u->getDisplayName(), $u->getId(), $u->getEmail()) : '--';
                        },
                        'title'   => __('Client', 'theme'),
                    ],
                    'lastname'           => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()) ? ($u->getLastName() ?: __('n.r.', 'theme')) : '--';
                        },
                        'title'   => __('Nom', 'theme'),
                    ],
                    'firstname'          => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()) ? ($u->getFirstName() ?: __('n.r.', 'theme')) : '--';
                        },
                        'title'   => __('Prénom', 'theme'),
                    ],
                    'company_name'       => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer()) ? ($u->getCompanyName() ?: __('n.r.', 'theme')) : '--';
                        },
                        'title'   => __('Société', 'theme'),
                    ],
                    'company_status'     => [
                        'content' => function (Item $order) {
                            return ($u = $order->getCustomer())
                                ? ($u->getCompanyStatus() ?: __('n.r.', 'theme')) : '--';
                        },
                        'title'   => __('Forme juridique', 'theme'),
                    ],
                    'transaction_id'     => [
                        'content' => function (Item $order) {
                            return $order->getTransactionId() ?: '--';
                        },
                        'title'   => __('Numéro de trans.', 'theme'),
                    ],
                    'payment_method'     => [
                        'content' => function (Item $order) {
                            return $order->getPaymentMethodTitle() ?: '--';
                        },
                        'title'   => __('Moyen de paiement', 'theme'),
                    ],
                    'payment_date'       => [
                        'content' => function (Item $order) {
                            return ($date = $order->getPaymentDatetime()) ? $date->format('d/m/Y H:i:s') : '--';
                        },
                        'title'   => __('Date de réglement', 'theme'),
                    ],
                    'total_with_tax'     => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalWithTax(), ...$format);
                        },
                        'title'   => __('Montant TTC', 'theme'),
                    ],
                    'total_without_tax'  => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalWithoutTax(), ...$format);
                        },
                        'title'   => __('Montant HT', 'theme'),
                    ],
                    'tax'                => [
                        'content' => function (Item $order) use ($format) {
                            return number_format($order->getTotalTax(), ...$format);
                        },
                        'title'   => __('Taxe', 'theme'),
                    ],
                    'billing_lastname'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('lastname');
                        },
                        'title'   => __('Factu. Nom', 'theme'),
                    ],
                    'billing_firstname'  => [
                        'content' => function (Item $order) {
                            return $order->getBilling('firstname');
                        },
                        'title'   => __('Factu. Prénom', 'theme'),
                    ],
                    'billing_company'    => [
                        'content' => function (Item $order) {
                            return $order->getBilling('company');
                        },
                        'title'   => __('Factu. Société', 'theme'),
                    ],
                    'billing_address1'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('address1');
                        },
                        'title'   => __('Factu. Adresse', 'theme'),
                    ],
                    'billing_address2'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('address2');
                        },
                        'title'   => __('Factu. Comp. Adresse', 'theme'),
                    ],
                    'billing_postcode'   => [
                        'content' => function (Item $order) {
                            return $order->getBilling('postcode');
                        },
                        'title'   => __('Factu. C.P.', 'theme'),
                    ],
                    'billing_city'       => [
                        'content' => function (Item $order) {
                            return $order->getBilling('city');
                        },
                        'title'   => __('Factu. Ville', 'theme'),
                    ],
                    'billing_phone'      => [
                        'content' => function (Item $order) {
                            return $order->getBilling('phone');
                        },
                        'title'   => __('Factu. Tél', 'theme'),
                    ],
                    'billing_email'      => [
                        'content' => function (Item $order) {
                            return $order->getBilling('email');
                        },
                        'title'   => __('Factu. Email', 'theme'),
                    ],
                    'shipping_lastname'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('lastname');
                        },
                        'title'   => __('Liv. Nom', 'theme'),
                    ],
                    'shipping_firstname' => [
                        'content' => function (Item $order) {
                            return $order->getShipping('firstname');
                        },
                        'title'   => __('Liv. Prénom', 'theme'),
                    ],
                    'shipping_company'   => [
                        'content' => function (Item $order) {
                            return $order->getShipping('company');
                        },
                        'title'   => __('Liv. Société', 'theme'),
                    ],
                    'shipping_address1'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('address1');
                        },
                        'title'   => __('Liv. Adresse', 'theme'),
                    ],
                    'shipping_address2'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('address2');
                        },
                        'title'   => __('Liv. Comp. Adresse', 'theme'),
                    ],
                    'shipping_postcode'  => [
                        'content' => function (Item $order) {
                            return $order->getShipping('postcode');
                        },
                        'title'   => __('Liv. C.P.', 'theme'),
                    ],
                    'shipping_city'      => [
                        'content' => function (Item $order) {
                            return $order->getShipping('city');
                        },
                        'title'   => __('Liv. Ville', 'theme'),
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
