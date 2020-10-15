<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Console;

use Exception;
use tiFy\Plugins\Subscription\{QuerySubscription, SubscriptionAwareTrait};
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use tiFy\Console\Command as BaseCommand;
use tiFy\Support\DateTime;

class RenewNotifyCommand extends BaseCommand
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $subscriptions = $this->subscription()->fetch([
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => '_end_date',
                    'value'   => DateTime::now(DateTime::getGlobalTimeZone()),
                    'compare' => '>=',
                    'type'    => 'DATETIME',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_renew_notified',
                        'value'   => '',
                        'compare' => '!='
                    ],
                    [
                        'key'     => '_renew_notified',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ],
            'posts_per_page' => -1,
            'order'          => 'DESC'
        ]);

        $notify = (new Collection($subscriptions))->filter(function (QuerySubscription $item) {
            return $item->mustRenewNotify();
        })->all();

        /** @var QuerySubscription $item */
        if ($notify) {
            foreach ($notify as $item) {
                if ($mail = $item->renewNotifyMail()) {
                    $mail->send();

                    $item->saveMeta('_renew_notified',
                        DateTime::now(DateTime::getGlobalTimeZone())->format('Y-m-d H:i:s')
                    );

                    $user = $item->getCustomer();

                    $this->message('info', sprintf(
                            __('Mail d\'invite de ré-engagement à l\'abonnement n°%d expédié à #%d - %s >> %s', 'tify'),
                            $item->getId(), $user->getId(), $user->getDisplayName(), $user->getEmail()
                        )
                    );
                } else {
                    $this->message('error', sprintf(
                        __('Echec d\'expédition du mail d\'invite de ré-engagement à l\'abonnement n°%d', 'tify'),
                        $item->getId(),
                    ));
                }

                $this->handleNotices($output);
            }
        } else {
            $this->message('notice', __('Aucune invite de ré-engagement ne doit être expédiée.', 'tify'));

            $this->handleNotices($output);
        }

        return 0;
    }
}