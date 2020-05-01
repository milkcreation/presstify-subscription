<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use App\Wordpress\QueryUser;
use Exception;
use Psr\Container\ContainerInterface as Container;
use tiFy\Contracts\{Log\Logger, Partial\FlashNotice, Routing\Route};
use tiFy\Plugins\Subscription\{Export\Export, Gateway\Gateway, Order\Order, Offer\Offer};
use tiFy\Support\{ParamsBag, Str};
use tiFy\Support\Proxy\{Log, Partial, PostType, Router, View};
use tiFy\Wordpress\Contracts\Query\{QueryPost as QueryPostContract, QueryUser as QueryUserContract};
use WP_Post, WP_Query, WP_User;

/**
 * @desc Extension PresstiFy de gestion d'abonnements.
 * @author Jordy Manner <jordy@milkcreation.fr>
 * @package tiFy\Plugins\Subscription
 * @version 2.0.0
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
     * Instance du controleur de traitement des requêtes de paiement.
     * @var SubscriptionController
     */
    protected $controller;

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
                    $m['page_title'] ?? __('Gestion des abonnements', 'theme'),
                    $m['menu_title'] ?? __('Abonnements', 'theme'),
                    $m['capability'] ?? 'edit_posts',
                    $m['menu_slug'] ?? 'subscription',
                    $m['function'] ?? '__return_false',
                    $m['icon_url'] ?? 'dashicons-id',
                    $m['position'] ?? 15
                );

                $s = $this->config('subscription.admin_menu', []);
                add_submenu_page(
                    $s['parent_slug'] ?? ($m['menu_slug'] ?? 'subscription'),
                    $s['page_title'] ?? __('Liste des abonnements', 'theme'),
                    $s['menu_title'] ?? __('Abonnements', 'theme'),
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
                'plural'              => __('Abonnements', 'theme'),
                'singular'            => __('Abonnement', 'theme'),
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

            /* ROUTAGE */
            // - Définition du routage de traitement des requêtes de paiement.
            $controller = $this->config('routing.controller', null);
            if (is_string($controller) && class_exists($controller)) {
                $controller = new $controller();
            }

            $this->controller = $controller instanceof SubscriptionController
                ? $controller : new SubscriptionController();

            $this->controller->setSubscription($this);

            $pfx = 'subscription';
            $endpoints = array_merge([
                'handle-failed'    => md5("{$pfx}/failed"),
                'handle-cancelled' => md5("{$pfx}/cancelled"),
                'handle-ipn'       => md5("{$pfx}/ipn"),
                'handle-pending'   => md5("{$pfx}/pending"),
                'handle-successed' => md5("{$pfx}/successed"),
                'payment-error'    => md5("{$pfx}/payment-error"),
                'payment-form'     => md5("{$pfx}/payment-form"),
                'payment-success'  => md5("{$pfx}/thank-you"),
            ], $this->config('routing.endpoints', []));

            array_walk($endpoints, function (&$endpoint) {
                $regex = '/\{order_key.*?\}/';

                if (!preg_match($regex, $endpoint)) {
                    $endpoint = rtrim($endpoint, '/') . '/{order_key}';
                }
            });

            foreach ($endpoints as $name => $endpoint) {
                $this->route[$name] = Router::get($endpoint, [$this->controller, Str::camel($name)]);
                $this->route["{$name}-post"] = Router::post($endpoint, [$this->controller, Str::camel($name)]);
            }

            View::addFolder('subscription', $this->resources('/views/checkout'));

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

        throw new Exception(__('Impossible de récupérer l\'instance du gestionnaire d\'abonnements.', 'theme'));
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
        return $this->controller;
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

    /**
     * Instance de l'utilisateur courant ou de l'utilisateur associé à un identifiant de qualification.
     *
     * @param string|int|WP_User|null $id
     *
     * @return QueryUser
     */
    public function user($id = null): ?QueryUserContract
    {
        return QueryUser::create($id);
    }
}