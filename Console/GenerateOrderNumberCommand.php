<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Console;

use Exception;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use tiFy\Console\Command as BaseCommand;

class GenerateOrderNumberCommand extends BaseCommand
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

        $orders = $this->subscription()->order()->fetch(array_merge([
            'posts_per_page' => -1,
            'orderby'        => ['ID' => 'ASC']
        ], $args));

        $renew = filter_var($input->getOption('renew') ?: false, FILTER_VALIDATE_BOOL);

        if ($orders) {
            foreach ($orders as $order) {
                if (($number = $order->getNumber()) && !$renew) {
                    $this->message('info', sprintf(
                        __('La commande #%d >> n°%s, ne nécessite aucun changement.', 'tify'), $order->getId(), $number
                    ));

                    continue;
                }

                $number = $this->subscription()->order()->generateUniqueNumber(null, $order);
                $order->set('order_number', $number)->update();

                $this->message('success', sprintf(
                    __('Le numéro n°%s a été affecté à la commande #%d.', 'tify'), $number, $order->getId()
                ));
            }
        } else {
            $this->message('info', __('Aucune commande ne correspond aux critères.', 'tify'));
        }

        $this->handleNotices($output);

        return 0;
    }
}