<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Proxy;

use tiFy\Contracts\{Log\Logger, Routing\Route};
use tiFy\Plugins\Subscription\{
    Export\Export,
    Gateway\Gateway,
    Mail\Mail,
    Offer\Offer,
    Order\Order,
    QuerySubscription,
    Subscription as SubscriptionManager,
    SubscriptionController,
    SubscriptionCustomer,
    SubscriptionFunctions,
    SubscriptionOrderForm,
    SubscriptionSession,
    SubscriptionSettings
};
use tiFy\Support\{ParamsBag, Proxy\AbstractProxy};
use tiFy\Wordpress\Contracts\Query\QueryUser;
use WP_Post, WP_User, WP_Query;

/**
 * @method static mixed|ParamsBag config(string|array|null $key = null, mixed $default = null)
 * @method static SubscriptionController|null controller()
 * @method static SubscriptionCustomer|null customer(string|int|QueryUser|WP_User|null $id)
 * @method static Export|null export()
 * @method static QuerySubscription[]|[] fetch(WP_Query|array|null $query)
 * @method static SubscriptionOrderForm form()
 * @method static SubscriptionFunctions|null functions()
 * @method static Gateway|null gateway()
 * @method static QuerySubscription|null get(string|int|WP_Post|null $post)
 * @method static Logger|null log()
 * @method static Mail|null mail()
 * @method static SubscriptionManager notify(string $message, string $type = 'error', array $args = [])
 * @method static Offer|null offer()
 * @method static Order|null order()
 * @method static Route|null route(string $name)
 * @method static SubscriptionSession session()
 * @method static SubscriptionSettings settings()
 */
class Subscription extends AbstractProxy
{
    /**
     * {@inheritDoc}
     *
     * @return SubscriptionManager
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @inheritDoc
     */
    public static function getInstanceIdentifier(): string
    {
        return 'subscription';
    }
}