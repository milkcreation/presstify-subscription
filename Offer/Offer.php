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
                    $this->subscription()->config('admin_menu.menu_slug', 'subscription'),
                    __('Liste des offres', 'tify'),
                    __('Offres', 'tify'),
                    'edit_posts',
                    'edit.php?post_type=subscription-offer',
                    '',
                    1
                );
            });
            /**/

            /* Déploiement du menu */
            add_action('admin_head', function () {
                global $parent_file, $post_type;

                switch ($post_type) {
                    case 'subscription-offer':
                        $parent_file = 'subscription';
                        break;
                }
            });
            /**/

            /* Tri des offres depuis l'interface d'administration */
            add_action('pre_get_posts', function (WP_Query $wp_query) {
                if (is_admin() && $wp_query->is_main_query() &&
                    (get_current_screen()->id === 'edit-subscription-offer') && !$wp_query->get('orderby')
                ) {
                    $wp_query->set('orderby', ['menu_order' => 'ASC']);
                }
            });
            /**/

            /* TYPE DE POST */
            PostType::register('subscription-offer', [
                'plural'             => __('Offres', 'tify'),
                'singular'           => __('Offre', 'tify'),
                'gender'             => true,
                'hierarchical'       => false,
                'publicly_queryable' => false,
                'show_in_menu'       => false,
                'supports'           => ['title', 'page-attributes'],
            ]);
            /**/

            /* METADONNES */
            PostType::meta()
                ->registerSingle('subscription-offer', '_limited_length')
                ->registerSingle('subscription-offer', '_limited_unity')
                ->registerSingle('subscription-offer', '_label')
                ->registerSingle('subscription-offer', '_limited')
                ->registerSingle('subscription-offer', '_price')
                ->registerSingle('subscription-offer', '_renewable')
                ->registerSingle('subscription-offer', '_renew_days')
                ->registerSingle('subscription-offer', '_renew_notify')
                ->registerSingle('subscription-offer', '_renew_notify_days')
                ->registerSingle('subscription-offer', '_sku')
                ->registerSingle('subscription-offer', '_tax');
            /**/

            /* COLONNES */
            Column::stack('subscription-offer@post_type', [
                'offer-price'   => [
                    'content'  => OfferPriceColumn::class,
                    'position' => 2,
                    'viewer'   => [
                        'directory' => $this->subscription()->resources('/views/admin/column/post-type/offer-price'),
                    ],
                ],
                'offer-details' => [
                    'content'  => OfferDetailsColumn::class,
                    'position' => 2.1,
                    'viewer'   => [
                        'directory' => $this->subscription()->resources('/views/admin/column/post-type/offer-details'),
                    ],
                ],
                'offer-order'   => [
                    'content'  => MenuOrder::class,
                    'position' => 2.2,
                ],
            ]);
            /**/

            /* METABOXES */
            Metabox::add('offer-details', [
                'params' => [
                    'device'    => $this->subscription()->functions()->getCurrencySymbol(),
                    'taxable'   => $this->subscription()->settings()->isTaxEnabled(),
                    'tax_label' => $this->subscription()->settings()->isPricesIncludeTax()
                        ? __('TTC', 'tify') : __('HT', 'tify'),
                ],
                'title'  => __('Détails du produit', 'tify'),
                'viewer' => [
                    'directory' => $this->subscription()->resources('/views/admin/metabox/post-type/offer-details'),
                ],
            ])
                ->setScreen('subscription-offer@post_type')->setContext('tab')
                ->setHandler(function ($box, WP_Post $wp_post) {
                    $box->set([
                        'offer'    => $this->subscription()->offer()->get($wp_post),
                        'settings' => $this->subscription()->settings()
                    ]);
                });
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