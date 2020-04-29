<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use App\Wordpress\QueryUser;
use Exception;
use Psr\Container\ContainerInterface as Container;
use tiFy\Contracts\View\Engine;
use tiFy\Plugins\Subscription\{Export\Export, Order\Order, Offer\Offer};
use tiFy\Support\{ParamsBag, Proxy\View};
use tiFy\Wordpress\Contracts\Query\QueryUser as QueryUserContract;
use WP_User;

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
     * Instance du gestionnaire des gabarits d'affichage.
     * @var View|null
     */
    protected $view;

    /**
     * CONSTRUCTEUR.
     *
     * @param Container $container
     *
     * @return void
     */
    public function __construct(Container $container = null)
    {
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
            add_action('admin_menu', function () {
                add_menu_page(
                    __('Gestion des adhésions', 'theme'),
                    __('Adhesions', 'theme'),
                    'edit_posts',
                    'subscription',
                    '__return_false',
                    'dashicons-id',
                    15
                );
            });
            /**/

            /* INITIALISATION */
            $this->export()->boot();
            $this->offer()->boot();
            $this->order()->boot();
            $this->settings()->boot();
            $this->session()->boot();
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
     * Récupération du conteneur d'injection de dépendances.
     *
     * @return Container|null
     */
    public function getContainer(): ?Container
    {
        return $this->container;
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
     * Instance du gestionnaire des fonctions globales.
     *
     * @return SubscriptionFunctions
     */
    public function functions(): ?SubscriptionFunctions
    {
        return $this->resolve('functions');
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

    /**
     * Récupération de l'instance du gestionnaire de gabarit d'affichage.
     *
     * @return Engine
     */
    public function view(): Engine
    {
        if (is_null($this->view)) {
            if (($view = $this->config('view', [])) && ($view instanceof Engine)) {
                $this->view = $view;
            } else {
                $this->view = View::getPlatesEngine(array_merge([
                    'directory' => dirname(__FILE__) . '/Resources/views'
                ], is_array($view) ? $view : []));
            }
        }

        return $this->view;
    }
}