<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Invoice;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Contracts\Partial\Modal;
use tiFy\Partial\PartialDriver;
use tiFy\Support\Proxy\Partial;

class InvoicePartial extends PartialDriver
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
            'content' => __('Facture', 'tify'),
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
                    'body'   => '',
                    'footer' => Partial::get('tag', [
                        'attrs'   => [
                            'href' => route('account.order.invoice.pdf-download', [$order], false),
                        ],
                        'content' => __('Télécharger', 'tify'),
                        'tag'     => 'a',
                    ])->render(),
                    'header' => '<h3 class="modal-title">' .
                            sprintf(__('Facture | Commande n°%d', 'tify'), $order) .
                        '</h3>',
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