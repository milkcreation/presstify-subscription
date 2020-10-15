<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\Order;

use tiFy\Template\Templates\ListTable\{Contracts\Extra as BaseExtraContract, Extra};
use tiFy\Support\Proxy\Request;
use tiFy\Validation\Validator as v;

class ExtraFilter extends Extra
{
    /**
     * @inheritDoc
     */
    public function defaults(): array
    {
        return array_merge(parent::defaults(), [
            'which' => 'top',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function parse(): BaseExtraContract
    {
        parent::parse();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        $from = (($f = Request::input('paid_from')) && v::date('d/m/Y')->validate($f)) ? $f : '';
        $to = (($t = Request::input('paid_to')) && v::date('d/m/Y')->validate($t)) ? $t : '';

        $this->set('paid_from', [
            'attrs' => [
                'autocomplete' => 'off',
                'placeholder'  => __('Date de début de réglement', 'tify'),
                'readonly',
            ],
            'name'  => 'paid_from',
            'options' => [
                'maxDate' => $to ?: null,
            ],
            'value' => $from
        ]);

        $this->set('paid_to', [
            'attrs' => [
                'autocomplete' => 'off',
                'placeholder'  => __('Date de fin de réglement', 'tify'),
                'readonly',
            ],
            'name'  => 'paid_to',
            'options' => [
                'minDate' => $from ?: null,
                'maxDate' => 0
            ],
            'value' => $to
        ]);

        $this->set('paid_filter', [
            'attrs' => [
                'class' => 'button-secondary'
            ],
            'type'  => 'submit',
            'content' => __('Filtrer', 'tify')
        ]);

        return $this->factory->viewer('extra-filter', $this->all());
    }
}