<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use App\Wordpress\QueryUser;
use Exception;
use Psr\Container\ContainerInterface as Container;
use tiFy\Contracts\{Log\Logger, Partial\FlashNotice, Routing\Route};
use tiFy\Plugins\Subscription\{
    Column\SubscriptionDetailsColumn,
    Column\SubscriptionExpirationColumn,
    Column\SubscriptionCustomerColumn,
    Column\UserSubscriptionColumn,
    Export\Export,
    Gateway\Gateway,
    Mail\Mail,
    Mail\OrderMail,
    Order\Order,
    Offer\Offer
};
use tiFy\Support\{ParamsBag, Str};
use tiFy\Support\Proxy\{Column, Form, Log, Metabox, Partial, PostType, Router, View};
use tiFy\Wordpress\Contracts\Query\{QueryPost as QueryPostContract, QueryUser as QueryUserContract};
use tiFy\Metabox\MetaboxDriver;
use WP_Post, WP_Query, WP_User;

/**
 * @desc Extension PresstiFy de gestion d'abonnements.
 * @author Jordy Manner <jordy@milkcreation.fr>
 * @package tiFy\Plugins\Subscription
 * @version 2.0.1
 *
 * USAGE :
 * Activation
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans config/app.php ajouter \tiFy\Plugins\Subscription\SubscriptionServiceProvider à la liste des
 *     fournisseurs de services. ex.
 * <?php
 * ...
 * use tiFy\Plugins\Subscription\SubscriptionServiceProvider;
 * ...
 *
 * return [
 *      ...
 *      'providers' => [
 *          ...
 *          SubscriptionServiceProvider::class
 *          ...
 *      ]
 * ];
 *
 * Configuration
 * ---------------------------------------------------------------------------------------------------------------------
 * Dans le dossier de config, créer le fichier subscription.php
 * @see /vendor/presstify-plugins/subscription/Resources/config/subscription.php
 */
class Subscription
{
    /**
     * Instance de la classe.
     * @var static|null
     */
    protected static $instance;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    protected $booted = false;

    /**
     * Instance du gestionnaire de configuration.
     * @var ParamsBag
     */
    protected $config;

    /**
     * Instance du conteneur d'injection de dépendances.
     * @var Container|null
     */
    protected $container;

    /**
     * Liste des services par défaut fournis par conteneur d'injection de dépendances.
     */
    protected $defaultService = [
        'controller' => SubscriptionController::class,
        'customer'   => SubscriptionCustomer::class,
        'export'     => Export::class,
        'form'       => SubscriptionOrderForm::class,
        'functions'  => SubscriptionFunctions::class,
        'gateway'    => Gateway::class,
        'mail'       => Mail::class,
        'mail.order' => OrderMail::class,
        'offer'      => Offer::class,
        'order'      => Order::class,
        'settings'   => SubscriptionSettings::class,
        'session'    => SubscriptionSession::class,
    ];

    /**
     * Instance du gestionnaire de journalisation des événements.
     * @var Logger|null
     */
    protected $log;

    /**
     * Liste des routes de traitement des requêtes de paiement.
     * @var Route[]|array
     */
    protected $route = [];

    /**
     * Instance du gestionnaire des gabarits d'affichage.
     * @var View|null
     */
    protected $view;

    /**
     * CONSTRUCTEUR.
     *
     * @param array $config
     * @param Container $container
     *
     * @return void
     */
    public function __construct(array $config = [], Container $container = null)
    {
        $this->setConfig($config);

        if (!is_null($container)) {
            $this->setContainer($container);
        }

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): self
    {
        if (!$this->booted) {
            /* MENU D'ADMINISTRATION */
            // -- Déclaration des entrées de menu
            add_action('admin_menu', function () {
                $m = $this->config('admin_menu', []);

                add_menu_page(
                    $m['page_title'] ?? __('Gestion des abonnements', 'tify'),
                    $m['menu_title'] ?? __('Abonnements', 'tify'),
                    $m['capability'] ?? 'edit_posts',
                    $m['menu_slug'] ?? 'subscription',
                    $m['function'] ?? '__return_false',
                    $m['icon_url'] ?? 'dashicons-id',
                    $m['position'] ?? 15
                );

                $s = $this->config('subscription.admin_menu', []);
                add_submenu_page(
                    $s['parent_slug'] ?? ($m['menu_slug'] ?? 'subscription'),
                    $s['page_title'] ?? __('Liste des abonnements', 'tify'),
                    $s['menu_title'] ?? __('Abonnements', 'tify'),
                    $s['capability'] ?? 'edit_posts',
                    $s['menu_slug'] ?? 'edit.php?post_type=subscription',
                    $s['function'] ?? '',
                    $s['position'] ?? 0
                );
            });
            /**/

            /* Déploiement du menu */
            add_action('admin_head', function () {
                global $parent_file, $post_type;

                switch ($post_type) {
                    case 'subscription':
                        $parent_file = 'subscription';
                        break;
                }
            });
            /**/

            /* Désactivation de l'entrée de sous menu principale */
            add_filter('submenu_file', function ($submenu_file) {
                $slug = $this->config('admin_menu.menu_slug', 'subscription');
                remove_submenu_page($slug, $slug);

                return $submenu_file;
            });
            /**/

            /* Suppression de la metaboxe d'enregistrement native */
            add_action('add_meta_boxes', function () {
                remove_meta_box('submitdiv', 'subscription', 'side');
            });
            /**/

            /* INITIALISATION */
            $this->settings()->boot();
            $this->offer()->boot();
            $this->order()->boot();
            $this->gateway()->boot();
            $this->export()->boot();
            $this->session()->boot();
            /**/

            /* TYPES DE POST */
            // - Déclaration des abonnements.
            PostType::register('subscription', array_merge([
                'plural'              => __('Abonnements', 'tify'),
                'singular'            => __('Abonnement', 'tify'),
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'hierarchical'        => false,
                'show_in_menu'        => false,
                'show_in_nav_menus'   => false,
                'rewrite'             => false,
                'query_var'           => false,
                'supports'            => [''],
                'has_archive'         => false,
            ], $this->config('subscription.post_type', [])));
            /**/

            /* COLONNES */
            // -- Page liste des abonnements.
            Column::stack('subscription@post_type', [
                'subscription-expiration' => [
                    'content'  => SubscriptionExpirationColumn::class,
                    'position' => 1,
                ],
                'subscription-user'       => [
                    'content'  => SubscriptionCustomerColumn::class,
                    'position' => 2.1,
                ],
                'subscription-details'    => [
                    'content'  => SubscriptionDetailsColumn::class,
                    'position' => 2.2,
                    'viewer'   => [
                        'directory' => $this->resources('/views/admin/column/post-type/subscription-details'),
                    ],
                ],
            ]);
            // - Page liste des utilisateurs.
            Column::add('@user', 'user-subscription', [
                'content'  => UserSubscriptionColumn::class,
                'position' => 4,
                'viewer'   => [
                    'directory' => $this->resources('/views/admin/column/user/subscription'),
                ],
            ]);
            /**/

            /* METABOXES */
            PostType::meta()
                ->registerSingle('subscription', '_product_label')
                ->registerSingle('subscription', '_renewable_days')
                ->registerSingle('subscription', '_renew_notification');

            Metabox::add('subscription-actions', [
                'title'  => __('Actions sur l\'abonnement', 'tify'),
                'viewer' => [
                    'directory' => $this->resources('/views/admin/metabox/post-type/subscription-actions'),
                ],
            ])
                ->setScreen('subscription@post_type')->setContext('side')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set([
                        'subscription' => $this->get($wp_post),
                    ]);
                });

            Metabox::add('subscription-details', [
                'params' => [
                    'device'    => $this->functions()->getCurrencySymbol(),
                    'taxable'   => $this->settings()->isTaxEnabled(),
                    'tax_label' => $this->settings()->isPricesIncludeTax()
                        ? __('TTC', 'tify') : __('HT', 'tify'),
                ],
                'title'  => __('Détails de l\'abonnement', 'tify'),
                'viewer' => [
                    'directory' => $this->resources('/views/admin/metabox/post-type/subscription-details'),
                ],
            ])
                ->setScreen('subscription@post_type')->setContext('tab')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set([
                        'subscription' => $this->get($wp_post),
                    ]);
                });
            /**/

            /* ROUTAGE */
            $pfx = 'subscription';
            $endpoints = array_merge([
                'handle-failed'    => md5("{$pfx}/failed"),
                'handle-cancelled' => md5("{$pfx}/cancelled"),
                'handle-ipn'       => md5("{$pfx}/ipn"),
                'handle-on-hold'   => md5("{$pfx}/on-hold"),
                'handle-successed' => md5("{$pfx}/successed"),
                'order-form'       => md5("{$pfx}/order-form"),
                'order-renew'      => md5("{$pfx}/order-renew"),
                'payment-error'    => md5("{$pfx}/payment-error"),
                'payment-form'     => md5("{$pfx}/payment-form"),
                'payment-success'  => md5("{$pfx}/payment-success"),
            ], $this->config('endpoints', []));

            array_walk($endpoints, function (&$endpoint, $key) {
                if (!in_array($key, ['order-form', 'order-renew'])) {
                    $regex = '/\{order_key.*?\}/';

                    if (!preg_match($regex, $endpoint)) {
                        $endpoint = rtrim($endpoint, '/') . '/{order_key}';
                    }
                }
            });

            foreach ($endpoints as $name => $endpoint) {
                $this->route[$name] = Router::get($endpoint, [$this->controller(), Str::camel($name)]);
                $this->route["{$name}.post"] = Router::post($endpoint, [$this->controller(), Str::camel($name)]);
            }
            /**/

            /* FORMULAIRE */
            Form::set(md5('subscription.orderForm'), $this->form());
            /**/

            /* VUES */
            View::addFolder('subscription', $this->resources('/views/checkout'));
            /**/

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Récupération de l'instance courante.
     *
     * @return static
     *
     * @throws Exception
     */
    public static function instance(): self
    {
        if (self::$instance instanceof static) {
            return self::$instance;
        }

        throw new Exception(__('Impossible de récupérer l\'instance du gestionnaire d\'abonnements.', 'tify'));
    }

    /**
     * Récupération de paramètre|Définition de paramètres|Instance du gestionnaire de paramètre.
     *
     * @param string|array|null $key Clé d'indice du paramètre à récupérer|Liste des paramètre à définir.
     * @param mixed $default Valeur de retour par défaut lorsque la clé d'indice est une chaine de caractère.
     *
     * @return mixed|ParamsBag
     */
    public function config($key = null, $default = null)
    {
        if (!$this->config instanceof ParamsBag) {
            $this->config = new ParamsBag();
        }

        if (is_string($key)) {
            return $this->config->get($key, $default);
        } elseif (is_array($key)) {
            return $this->config->set($key);
        } else {
            return $this->config;
        }
    }

    /**
     * Récupération de l'instance du controleur.
     *
     * @return SubscriptionController|null
     */
    public function controller(): SubscriptionController
    {
        return $this->resolve('controller');
    }

    /**
     * Récupération de l'instance d'un client.
     *
     * @param string|int|QueryUserContract|null $id Identification du client email|user_id|query
     *
     * @return SubscriptionCustomer|null
     */
    public function customer($id = null): ?SubscriptionCustomer
    {
        /** @var SubscriptionCustomer $customer */
        return  ($customer = $this->resolve('customer')) ? $customer::create($id) : null;
    }

    /**
     * Instance du gestionnaire d'export.
     *
     * @return Export|null
     */
    public function export(): ?Export
    {
        return $this->resolve('export');
    }

    /**
     * Liste des instances d'abonnements courants|associés à une requête WP_Query|associés à des arguments fournis.
     *
     * @param WP_Query|array|null $query
     *
     * @return QuerySubscription[]|array
     */
    public function fetch($query = null): array
    {
        return QuerySubscription::fetch($query);
    }

    /**
     * Instance du formulaire d'abonnement.
     *
     * @return SubscriptionOrderForm|null
     */
    public function form(): SubscriptionOrderForm
    {
        return $this->resolve('form');
    }

    /**
     * Instance du gestionnaire des fonctions globales.
     *
     * @return SubscriptionFunctions
     */
    public function functions(): ?SubscriptionFunctions
    {
        return $this->resolve('functions');
    }

    /**
     * Instance du gestionnaire de plateforme de paiement.
     *
     * @return Gateway|null
     */
    public function gateway(): ?Gateway
    {
        return $this->resolve('gateway');
    }

    /**
     * Instance de l'abonnement courant ou d'un abonnement associé à l'identifiant de qualification fourni.
     *
     * @param string|int|WP_Post|null $post
     *
     * @return QuerySubscription
     */
    public function get($post = null): ?QueryPostContract
    {
        return QuerySubscription::create($post);
    }

    /**
     * Récupération du conteneur d'injection de dépendances.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * Récupération de l'instance du gestionnaire de journalisation.
     *
     * @return Logger
     */
    public function log(): Logger
    {
        if (is_null($this->log)) {
            $this->log = Log::registerChannel('subscription');
        }

        return $this->log;
    }

    /**
     * Instance du gestionnaire de mails.
     *
     * @return Mail|null
     */
    public function mail(): ?Mail
    {
        return $this->resolve('mail');
    }

    /**
     * Message de notification.
     *
     * @param string $message
     * @param string $type error|warning|success|info
     * @param array $args
     *
     * @return $this
     */
    public function notify(string $message, string $type = 'error', array $args = []): self
    {
        /** @var FlashNotice $notice */
        $notice = Partial::get('flash-notice');

        $notice->add($message, $type, $args);

        return $this;
    }

    /**
     * Instance du gestionnaire d'offres d'abonnement.
     *
     * @return Offer|null
     */
    public function offer(): ?Offer
    {
        return $this->resolve('offer');
    }

    /**
     * Instance du gestionnaire de commandes.
     *
     * @return Order|null
     */
    public function order(): ?Order
    {
        return $this->resolve('order');
    }

    /**
     * Résolution de service fourni par le gestionnaire d'abonnments.
     *
     * @param string $alias
     *
     * @return object|mixed|null
     */
    public function resolve(string $alias)
    {
        return $this->container->get("subscription.{$alias}");
    }

    /**
     * Vérification de résolution possible d'un service fourni par le gestionnaire d'abonnments.
     *
     * @param string $alias
     *
     * @return bool
     */
    public function resolvable(string $alias): bool
    {
        return $this->container->has("subscription.{$alias}");
    }

    /**
     * Récupération du chemin absolu vers le répertoire des ressources.
     *
     * @param string|null $path Chemin relatif d'une resource (répertoire|fichier).
     *
     * @return string
     */
    public function resources(string $path = null): string
    {
        $path = $path ? '/' . ltrim($path, '/') : '';

        return (file_exists(__DIR__ . "/Resources{$path}")) ? __DIR__ . "/Resources{$path}" : '';
    }

    /**
     * Récupération d'une route de traitement de requête de paiement.
     *
     * @param string $name checkout|cancelled|failed|successed|ipn
     *
     * @return Route|null
     */
    public function route(string $name): ?Route
    {
        return $this->route[$name] ?? null;
    }

    /**
     * Récupération d'un service.
     *
     * @param string $name
     *
     * @return callable|object|string|null
     */
    public function service(string $name)
    {
        return $this->config("service.{$name}", $this->defaultService[$name] ?? null);
    }

    /**
     * Instance de la session.
     *
     * @return SubscriptionSession
     */
    public function session(): ?SubscriptionSession
    {
        return $this->resolve('session');
    }

    /**
     * Instance du gestionnaire des réglages.
     *
     * @return SubscriptionSettings
     */
    public function settings(): ?SubscriptionSettings
    {
        return $this->resolve('settings');
    }

    /**
     * Définition des paramètres de configuration.
     *
     * @param array $attrs Liste des attributs de configuration.
     *
     * @return static
     */
    public function setConfig(array $attrs): self
    {
        $this->config($attrs);

        return $this;
    }

    /**
     * Définition du conteneur d'injection de dépendances.
     *
     * @param Container $container
     *
     * @return static
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }
}
