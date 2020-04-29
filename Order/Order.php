<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use Illuminate\Support\Collection;
use tiFy\Contracts\{Metabox\MetaboxDriver, PostType\PostTypeStatus};
use tiFy\Support\Proxy\{Column, Metabox, PostType};
use tiFy\Wordpress\Contracts\Query\{QueryPost as QueryPostContract, QueryUser as QueryUserContract};
use WP_Post, WP_Query, WP_User;

class Order
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Liste des instances de statuts de commande.
     * @var OrderStatus[]]|array
     */
    protected $statuses = [];

    /**
     * Initialisation.
     *
     * @return $this
     */
    public function boot(): self
    {
        if (!$this->booted) {
            /* MENU D'ADMINISTRATION */
            add_action('admin_menu', function () {
                add_submenu_page(
                    'subscription',
                    __('Liste des commandes', 'theme'),
                    __('Commandes', 'theme'),
                    'edit_posts',
                    'edit.php?post_type=subscription-order',
                    '',
                    1
                );
            });
            /**/

            /* Pré-requête de récupération des commandes */
            add_action('pre_get_posts', function (WP_Query $wp_query) {
                if ($wp_query->get('post_type') === 'subscription-order') {
                    $status = $wp_query->get('post_status');
                    if (!$status || $status === 'any') {
                        $wp_query->set('post_status', join(',', $this->statusNames()));
                    }
                }
            });
            /**/

            /* Sauvegarde des commandes * /
            add_action('save_post', function (int $id, WP_Post $post, bool $update) {
                // Bypass - S'il s'agit d'une routine de sauvegarde automatique.
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    return;
                    // Bypass - Si le script est executé via Ajax.
                } elseif (defined('DOING_AJAX') && DOING_AJAX) {
                    return;
                } elseif ($post->post_type !== 'subscription-order') {
                    return;
                } elseif (!$order = $this->get($id)) {
                    return;
                }

                if (is_admin() && ($paymentIntentId = Request::input('_stripe_payment_intent'))) {
                    if ($paymentIntent = $this->subscription()->stripePayment()->retrieve($paymentIntentId)) {
                        update_post_meta($id, '_stripe_payment_intent', $paymentIntentId);
                    }
                }
            }, 10, 3);
            /**/

            /* Masquage de la modification rapide */
            add_filter('post_row_actions', function (array $actions, WP_Post $post) {
                if ($post->post_type === 'subscription-order') {
                    unset($actions['inline hide-if-no-js']);
                }

                return $actions;
            }, 10, 2);
            /**/

            /* Suppression de la metaboxe d'enregistrement native */
            add_action('add_meta_boxes', function () {
                remove_meta_box('submitdiv', 'subscription-order', 'side');
            });
            /**/

            /* Modification du message de sauvegarde réussie */
            add_filter('post_updated_messages', function ($messages) {
                global $post;

                if ($post->post_type === 'subscription-order') {
                    $messages['post'][1] = __('Commande mise à jour', 'theme');
                }

                return $messages;
            });
            /**/

            /* TYPE DE POST */
            PostType::register('subscription-order', [
                'plural'              => __('Commandes', 'theme'),
                'singular'            => __('Commande', 'theme'),
                'gender'              => true,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'hierarchical'        => false,
                'show_in_menu'        => false,
                'show_in_nav_menus'   => false,
                'rewrite'             => false,
                'query_var'           => false,
                'supports'            => false,
                'has_archive'         => false,
            ]);
            /**/

            /* COLONNES */
            Column::stack('subscription-order@post_type', [
                'order-status' => [
                    'content'  => OrderStatusColumn::class,
                    'position' => 2,
                    'viewer'   => [
                        'directory' => get_template_directory() . '/views/admin/column/post-type/order-status',
                    ],
                ],
                'order-total'    => [
                    'content'  => OrderTotalColumn::class,
                    'position' => 2.1,
                    'viewer'   => [
                        'directory' => get_template_directory() . '/views/admin/column/post-type/order-total',
                    ],
                ],
            ]);
            /**/

            /* STATUT DE POST */
            /* Commande - Attente de paiement */
            $this->setStatus('pending', [
                'name'                      => 'subscription-order-pending',
                'label'                     => _x('En attente de paiement', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'En attente de paiement <span class="count">(%s)</span>',
                    'En attente de paiement <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - En préparation */
            $this->setStatus('processing', [
                'name'                      => 'subscription-order-processing',
                'label'                     => _x('En préparation', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'En préparation <span class="count">(%s)</span>',
                    'En préparation <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - En attente */
            $this->setStatus('on-hold', [
                'name'                      => 'subscription-order-on-hold',
                'label'                     => _x('En attente', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'En attente <span class="count">(%s)</span>',
                    'En attente <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - Terminée */
            $this->setStatus('completed', [
                'name'                      => 'subscription-order-completed',
                'label'                     => _x('Terminée', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'Terminée <span class="count">(%s)</span>',
                    'Terminée <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - Annulée */
            $this->setStatus('cancelled', [
                'name'                      => 'subscription-order-cancelled',
                'label'                     => _x('Annulée', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'Annulée <span class="count">(%s)</span>',
                    'Annulée <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - Remboursée */
            $this->setStatus('refunded', [
                'name'                      => 'subscription-order-refunded',
                'label'                     => _x('Remboursée', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'Remboursée <span class="count">(%s)</span>',
                    'Remboursée <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/
            /* Commande - Echouée */
            $this->setStatus('failed', [
                'name'                      => 'subscription-order-failed',
                'label'                     => _x('Echouée', 'order_status', 'theme'),
                'public'                    => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop(
                    'Echouée <span class="count">(%s)</span>',
                    'Echouée <span class="count">(%s)</span>',
                    'theme'
                ),
            ]);
            /**/

            /* METABOXES */
            Metabox::add('order-actions', [
                'title'  => __('Actions sur la commande', 'theme'),
                'viewer' => [
                    'directory' => get_template_directory() . '/views/admin/metabox/post-type/order-actions',
                ],
            ])
                ->setScreen('order@post_type')->setContext('side')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set('order', $this->subscription()->order()->get($wp_post));
                });

            Metabox::add('order-details', [
                'title'  => __('Détails de la commande', 'theme'),
                'viewer' => [
                    'directory' => get_template_directory() . '/views/admin/metabox/post-type/order-details',
                ],
            ])
                ->setScreen('order@post_type')->setContext('tab')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set('order', $this->subscription()->order()->get($wp_post));
                });

            Metabox::add('order-addresses', [
                'title'  => __('Adresses', 'theme'),
                'viewer' => [
                    'directory' => get_template_directory() . '/views/admin/metabox/post-type/order-addresses',
                ],
            ])->setScreen('order@post_type')->setContext('tab');

            Metabox::add('order-billing', [
                'parent' => 'order-addresses',
                'title'  => __('Facturation', 'theme'),
                'viewer' => [
                    'directory' => get_template_directory() . '/views/admin/metabox/post-type/order-addresses/billing',
                ],
            ])
                ->setScreen('order@post_type')->setContext('tab')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set('order', $this->subscription()->order()->get($wp_post));
                });

            Metabox::add('order-shipping', [
                'parent' => 'order-addresses',
                'title'  => __('Livraison', 'theme'),
                'viewer' => [
                    'directory' => get_template_directory() . '/views/admin/metabox/post-type/order-addresses/shipping',
                ],
            ])
                ->setScreen('order@post_type')->setContext('tab')
                ->setHandler(function (MetaboxDriver $box, WP_Post $wp_post) {
                    $box->set('order', $this->subscription()->order()->get($wp_post));
                });
            /**/

            /* PARTIALS * /
            Partial::register('order-invoice', (new OrderInvoice())->setSubscription($this->subscription));
            /**/

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Liste des instances de commandes courantes|associées à une requête WP_Query|associées à des arguments.
     *
     * @param WP_Query|array|null $query
     *
     * @return QueryOrder[]|array
     */
    public function fetch($query = null): array
    {
        return QueryOrder::fetch($query);
    }

    /**
     * Instance de la commande courante ou de la commande associée à un identifiant de qualification.
     *
     * @param string|int|WP_Post|null $post
     *
     * @return QueryOrder
     */
    public function get($post = null): ?QueryPostContract
    {
        return QueryOrder::create($post);
    }

    /**
     * Liste des instances de commandes associées à un utilisateur.
     *
     * @param WP_User|QueryUserContract|int|null $user
     * @param array $args Liste des arguments de récupération des commandes.
     *
     * @return QueryOrder[]|array
     */
    public function user($user = null, array $args = []): array
    {
        if (is_numeric($user)) {
            $user_id = $user;
        } elseif ($user instanceof WP_User) {
            $user_id = $user->ID;
        } elseif ($user instanceof QueryUserContract) {
            $user_id = $user->getId();
        } else {
            $user_id = $this->subscription()->user()->getId();
        }

        return QueryOrder::fetch(array_merge(['posts_per_page' => 10], $args, [
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_customer_user',
                    'value' => $user_id
                ]
            ]
        ]));
    }

    /**
     * Définition d'un statut de commande.
     *
     * @param string $alias Alias de qualification.
     * @param array $args Instance du statut de commande
     *
     * @return static
     */
    public function setStatus(string $alias, array $args = []): self
    {
        $name = $args['name'] ?? $alias;
        unset($args['name']);

        $this->statuses[$alias] = OrderStatus::create($name, $args)->setAlias($alias);

        return $this;
    }

    /**
     * Instance du statut par défaut.
     *
     * @return PostTypeStatus|null
     */
    public function statusDefault(): ?PostTypeStatus
    {
        return $this->status('pending');
    }

    /**
     * Instance d'un statut de commande.
     *
     * @param string $alias_or_name Alias ou nom de qualification du statut de commande
     *
     * @return PostTypeStatus|null
     */
    public function status(string $alias_or_name): ?PostTypeStatus
    {
        if (isset($this->statuses[$alias_or_name])) {
            return $this->statuses[$alias_or_name];
        } else {
            foreach ($this->statuses as $status) {
                if ($status->getName() === $alias_or_name) {
                    return $status;
                }
            }
        }

        return null;
    }

    /**
     * Récupération des nom de qualification des statut de commande.
     *
     * @param array|null $keys
     *
     * @return PostTypeStatus[]|array
     */
    public function statusNames(?array $keys = null): array
    {
        if (is_null($keys)) {
            $statuses = $this->statuses;
        } else {
            $statuses = [];

            foreach ($keys as $key) {
                if ($st = $this->status($key)) {
                    $statuses[$st->getAlias() ?: $st->getName()] = $st;
                }
            }
        }

        return (new Collection($statuses))->pluck('name')->all();
    }

    /**
     * Vérifie si un statut reclame le paiement.
     *
     * @param string $alias_or_name Alias ou nom de qualification du statut à vérifier.
     *
     * @return bool
     */
    public function statusNeedPaid(string $alias_or_name): bool
    {
        $statuses = $this->statuses($this->statusNeedPaymentNames());

        foreach ($statuses as $status) {
            if ($status->getName() === $alias_or_name || $status->getAlias() === $alias_or_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un statut reclame le paiement.
     *
     * @return string[]
     */
    public function statusNeedPaymentNames(): array
    {
        return ['order-failed', 'order-pending'];
    }

    /**
     * Vérifie si un statut défini le paiement complet.
     *
     * @param string $alias_or_name Alias ou nom de qualification du statut à vérifier.
     *
     * @return bool
     */
    public function statusPaymentCompleted(string $alias_or_name): bool
    {
        $statuses = $this->statuses($this->statusPaymentCompleteNames());

        foreach ($statuses as $status) {
            if ($status->getName() === $alias_or_name || $status->getAlias() === $alias_or_name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si un statut défini le paiement complet.
     *
     * @return string[]
     */
    public function statusPaymentCompleteNames(): array
    {
        return ['order-completed', 'order-processing'];
    }

    /**
     * Récupération des instance de statuts commande.
     *
     * @param array|null $keys
     *
     * @return PostTypeStatus[]|array
     */
    public function statuses(?array $keys = null): array
    {
        if (is_null($keys)) {
            return $this->statuses;
        } else {
            $statuses = [];

            foreach ($keys as $key) {
                if ($st = $this->status($key)) {
                    $statuses[$st->getAlias() ?: $st->getName()] = $st;
                }
            }
            return $statuses;
        }
    }
}