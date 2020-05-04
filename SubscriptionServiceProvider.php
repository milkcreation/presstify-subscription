<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Plugins\Subscription\{
    Export\Export as ExportManager,
    Gateway\Gateway as GatewayManager,
    Mail\Mail as MailManager,
    Mail\OrderMail,
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
        'subscription.controller',
        'subscription.customer',
        'subscription.export',
        'subscription.form',
        'subscription.functions',
        'subscription.gateway',
        'subscription.mail',
        'subscription.mail.order',
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

        $this->getContainer()->share('subscription.controller', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('controller');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionController ? $service : new SubscriptionController();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.customer', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('customer');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionCustomer ? $service : new SubscriptionCustomer();

            return $service->setSubscription($manager);
        });


        $this->getContainer()->share('subscription.export', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('export');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof ExportManager ? $service : new ExportManager();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.form', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('form');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionOrderForm ? $service : new SubscriptionOrderForm();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.functions', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('functions');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionFunctions ? $service : new SubscriptionFunctions();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.gateway', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('gateway');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof GatewayManager ? $service : new GatewayManager();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.mail', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('mail');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof MailManager ? $service : new MailManager();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.mail.order', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('mail.order');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof OrderMail ? $service : new OrderMail();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.offer', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('offer');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof OfferManager ? $service : new OfferManager();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.order', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('order');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof OrderManager ? $service : new OrderManager();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.settings', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('settings');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionSettings ? $service : new SubscriptionSettings();

            return $service->setSubscription($manager);
        });

        $this->getContainer()->share('subscription.session', function () {
            /** @var Subscription $manager */
            $manager = $this->getContainer()->get('subscription');

            $service = $manager->service('session');
            if (!is_object($service)) {
                $service = new $service;
            }

            $service = $service instanceof SubscriptionSession ? $service : new SubscriptionSession();

            return $service->setSubscription($manager);
        });
    }
}