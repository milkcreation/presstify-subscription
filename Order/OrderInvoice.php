<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Contracts\Partial\Modal;
use tiFy\Partial\PartialDriver;
use tiFy\Support\Proxy\Partial;

class OrderInvoice extends PartialDriver
{
    use SubscriptionAwareTrait;

    /**
     * Instance de la modale associée.
     * @var Modal
     */
    protected $modal;

    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return [
            'attrs'   => [
                'class' => 'Button--1',
            ],
            'content' => __('Facture', 'theme'),
            'order'   => 0,
        ];
    }

    /**
     * Récupération de l'instance de la modale.
     *
     * @return Modal
     */
    public function modal(): Modal
    {
        if (is_null($this->modal)) {
            $order = (int)$this->pull('order', 0);

            $this->modal = Partial::get('modal', [
                'ajax'    => [
                    'url' => route('account.order.invoice-xhr', [$order], false),
                ],
                'attrs'   => [
                    'class' => '%s OrderInvoice',
                ],
                'content' => [
                    'body'   => '', //$this->app->img('svg/title/invoice.svg', ['class' => 'InvoiceModal-waitingIcon']),
                    'footer' => Partial::get('tag', [
                        'attrs'   => [
                            'class' => 'Button--1',
                            'href'  => route('account.order.invoice.pdf-download', [$order], false),
                        ],
                        'content' => __('Télécharger', 'theme'),
                        'tag'     => 'a',
                    ])->render(),
                    'header' => '<h3 class="modal-title Title--1">' . Partial::get('page-title', [
                        'icon'  => '',//$this->app->img('svg/title/invoice.svg'),
                        'label' => sprintf(__('Facture | Commande n°%d', 'theme'), $order),
                    ])->render() . '</h3>'
                    //'<h3 class="modal-title Title--1">' . sprintf(__('Facture | Commande n°%d', 'theme'), $order) . '</h3>',
                ],
                'options' => [
                    'show' => false,
                ],
                'size'    => 'lg',
            ]);

            $this->modal->render();
        }

        return $this->modal;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return $this->modal()->trigger($this->all());
    }
}