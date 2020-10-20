<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\Order;

use tiFy\Plugins\Transaction\Wordpress\Template\ExportListTableWpPost\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * @inheritDoc
     */
    public function registerFactoryExtras(): void
    {
        parent::registerFactoryExtras();

        $this->getContainer()->add($this->getFactoryAlias('extra.filter'), function () {
            return (new ExtraFilter())->setTemplateFactory($this->factory);
        });
    }
}