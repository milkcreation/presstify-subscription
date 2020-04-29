<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Offer;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Support\Proxy\{Column, Metabox, PostType};
use tiFy\PostType\Column\MenuOrder\MenuOrder;
use tiFy\Wordpress\Contracts\Query\QueryPost as QueryPostContract;
use WP_Post, WP_Query;

class Offer
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

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
                add_submenu_page(
                    'subscription',
                    __('Liste des offres', 'theme'),
                    __('Offres', 'theme'),
                    'edit_posts',
                    'edit.php?post_type=subscription-offer',
                    '',
                    2
                );
            });
            /**/

            add_action('pre_get_posts', function (WP_Query $wp_query) {
                if (is_admin() && $wp_query->is_main_query() && get_current_screen()->id === 'edit-offer') {
                    if (!$wp_query->get('orderby')) {
                        $wp_query->set('orderby', ['menu_order' => 'ASC']);
                    }
                }
            });

            /* TYPE DE POST */
            PostType::register('subscription-offer', [
                'plural'             => __('Offres', 'theme'),
                'singular'           => __('Offre', 'theme'),
                'hierarchical'       => false,
                'publicly_queryable' => false,
                'show_in_menu'       => false,
                'supports'           => ['title', 'page-attributes'],
            ]);
            /**/

            /* METADONNES */
            PostType::meta()
                ->registerSingle('offer', '_label')
                ->registerSingle('offer', '_sku')
                ->registerSingle('offer', '_duration_length')
                ->registerSingle('offer', '_duration_unity')
                ->registerSingle('offer', '_price')
                ->registerSingle('offer', '_tax')
                ->registerSingle('offer', '_renewable_days')
                ->registerSingle('offer', '_renew_notification');
            /**/

            /* COLONNES */
            Column::stack('offer@post_type', [
                'offer-price'   => [
                    'content'  => OfferPriceColumn::class,
                    'position' => 2,
                    'viewer'   => [
                        'directory' => get_template_directory() . '/views/admin/column/post-type/offer-price',
                    ],
                ],
                'offer-details' => [
                    'content'  => OfferDetailsColumn::class,
                    'position' => 2.1,
                    'viewer'   => [
                        'directory' => get_template_directory() . '/views/admin/column/post-type/offer-details',
                    ],
                ],
                'offer-order'   => [
                    'content'  => MenuOrder::class,
                    'position' => 2.2,
                ],
            ]);
            /**/

            /* METABOXES */
            Metabox::stack('offer@post_type', 'tab', [
                'offer-details' => [
                    'params' => [
                        'device'    => $this->subscription()->functions()->getCurrencySymbol(),
                        'taxable'   => $this->subscription()->settings()->isTaxEnabled(),
                        'tax_label' => $this->subscription()->settings()->isPricesIncludeTax()
                            ? __('TTC', 'theme') : __('HT', 'theme')
                    ],
                    'title'  => __('Détails du produit', 'theme'),
                    'viewer' => [
                        'directory' => get_template_directory() . '/views/admin/metabox/post-type/offer-details',
                    ],
                ],
            ]);
            /**/

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Liste des instances de produits courants|associés à une requête WP_Query|associés à des arguments.
     *
     * @param WP_Query|array|null $query
     *
     * @return QueryOffer[]|array
     */
    public function fetch($query = null): array
    {
        return QueryOffer::fetch($query);
    }

    /**
     * Instance du produit courant ou du produit associé à un identifiant de qualification.
     *
     * @param string|int|WP_Post|null $post
     *
     * @return QueryOffer
     */
    public function get($post = null): ?QueryPostContract
    {
        return QueryOffer::create($post);
    }
}