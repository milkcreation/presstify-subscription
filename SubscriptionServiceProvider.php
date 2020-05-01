<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Plugins\Subscription\{
    Export\Export as ExportManager,
    Gateway\Gateway as GatewayManager,
    Order\Order as OrderManager,
    Offer\Offer as OfferManager
};
use tiFy\Container\ServiceProvider;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Liste des noms de qualification des services fournis.
     * @internal requis. Tous les noms de qualification de services à traiter doivent être renseignés.
     * @var string[]
     */
    protected $provides = [
        'subscription',
        'subscription.functions',
        'subscription.export',
        'subscription.gateway',
        'subscription.offer',
        'subscription.order',
        'subscription.settings',
        'subscription.session'
    ];

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        add_action('init', function () {
            $this->getContainer()->get('subscription')->boot();
        });
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->getContainer()->share('subscription', function () {
            return new Subscription(config('subscription', []), $this->getContainer());
        });

        $this->getContainer()->share('subscription.functions', function () {
            return (new SubscriptionFunctions())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.export', function () {
            return (new ExportManager())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.gateway', function () {
            return (new GatewayManager())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.offer', function () {
            return (new OfferManager())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.order', function () {
            return (new OrderManager())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.settings', function () {
            return (new SubscriptionSettings())->setSubscription($this->getContainer()->get('subscription'));
        });

        $this->getContainer()->share('subscription.session', function () {
            return (new SubscriptionSession())->setSubscription($this->getContainer()->get('subscription'));
        });
    }
}