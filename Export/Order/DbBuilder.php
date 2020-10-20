<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Export\Order;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use tiFy\Wordpress\Template\Templates\PostListTable\DbBuilder as BaseDbBuilder;
use tiFy\Wordpress\Contracts\Database\PostBuilder;
use tiFy\Support\DateTime;
use tiFy\Support\Proxy\Request;
use tiFy\Validation\Validator as v;

class DbBuilder extends BaseDbBuilder
{
    use SubscriptionAwareTrait;

    /**
     * {@inheritDoc}
     *
     * @return PostBuilder
     */
    public function queryWhere(): EloquentBuilder
    {
        parent::queryWhere();

        if (($from = Request::input('paid_from')) && (v::date('d/m/Y')->validate($from))) {
            $from = DateTime::createFromFormat('d/m/Y', $from)->setTime(0, 0, 0)->timestamp;

            $this->query()->whereHas('meta', function (EloquentBuilder $query) use ($from) {
                $query->where('meta_key', '_date_paid')->where('meta_value', '>', $from);
            });
        }

        if (($to = Request::input('paid_to')) && (v::date('d/m/Y')->validate($to))) {
            $to = DateTime::createFromFormat('d/m/Y', $to)->setTime(23, 59, 59)->timestamp;

            $this->query()->whereHas('meta', function (EloquentBuilder $query) use ($to) {
                $query->where('meta_key', '_date_paid')->where('meta_value', '<', $to);
            });
        }

        return $this->query();
    }
}