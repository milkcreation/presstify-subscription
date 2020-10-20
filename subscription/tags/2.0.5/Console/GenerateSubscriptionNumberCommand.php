<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Console;

use Exception;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use tiFy\Console\Command as BaseCommand;

class GenerateSubscriptionNumberCommand extends BaseCommand
{
    use SubscriptionAwareTrait;

    /**
     * CONSTRUCTEUR.
     *
     * @param string|null $name
     *
     * @return void
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        $this
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, __(
                'Identifiant(s) de qualification (séparateur virgule)', 'tify'
            ), 0)
            ->addOption('renew', null, InputOption::VALUE_OPTIONAL, __(
                'Renouvelle le numéro des commandes si existant', 'tify'
            ), false);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = [];

        if ($ids = $input->getOption('id')) {
            $args['post__in'] = array_map('intval', explode(',', $ids));
        }

        $subscriptions = $this->subscription()->fetch(array_merge([
            'posts_per_page' => -1,
            'orderby'        => ['ID' => 'ASC']
        ], $args));

        $renew = filter_var($input->getOption('renew') ?: false, FILTER_VALIDATE_BOOL);

        if ($subscriptions) {
            foreach ($subscriptions as $subscription) {
                if (($number = $subscription->getNumber()) && !$renew) {
                    $this->message('info', sprintf(
                        __('L\'abonnement #%d >> n°%s, ne nécessite aucun changement.', 'tify'),
                        $subscription->getId(), $number
                    ));

                    continue;
                }

                $number = $this->subscription()->generateUniqueNumber(null, $subscription);
                $subscription->set('subscription_number', $number)->update();

                $this->message('success', sprintf(
                    __('Le numéro n°%s a été affecté à l\'abonnement #%d.', 'tify'), $number, $subscription->getId()
                ));
            }
        } else {
            $this->message('info', __('Aucun abonnement ne correspond aux critères.', 'tify'));
        }

        $this->handleNotices($output);

        return 0;
    }
}