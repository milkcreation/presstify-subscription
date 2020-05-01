<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Support\ParamsBag;
use tiFy\Support\Proxy\Metabox;
use tiFy\Wordpress\Proxy\Option;

class SubscriptionSettings
{
    use SubscriptionAwareTrait;

    /**
     * Indicateur d'initialisation.
     * @var bool
     */
    private $booted = false;

    /**
     * Instance du gestionnaire de paramètres.
     * @var ParamsBag
     */
    protected $param;

    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): self
    {
        if (!$this->booted) {
            /* OPTIONS */
            $optionPage = Option::registerPage('subscription-settings', [
                'admin_menu' => [
                    'menu_title'  => __('Réglages', 'theme'),
                    'parent_slug' => 'subscription',
                    'position'    => 4,
                ],
            ]);

            $optionPage->registerSettings([
                'subscription_price',
                'subscription_offer',
            ]);
            /**/

            $this->param([
                'price' => array_merge(
                    $this->subscription()->config('settings.price', []),
                    get_option('subscription_price') ?: []
                ),
                'offer' => array_merge(
                    $this->subscription()->config('settings.offer', []),
                    get_option('subscription_offer') ?: []
                ),
            ]);

            /* METABOXES */
            $path = dirname(__FILE__) . '/Resources/views/admin/metabox/options';
            // -- Tarification
            Metabox::add('subscription-price', [
                'name'   => 'subscription_price',
                'title'  => __('Tarification', 'theme'),
                'viewer' => [
                    'directory' => $path . '/price',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            // -- Offres
            Metabox::add('subscription-offer', [
                'name'   => 'subscription_offer',
                'title'  => __('Offres', 'theme'),
                'viewer' => [
                    'directory' => $path . '/offer',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            /*'subscription-desc'   => [
                'title'  => __('Présentation des abonnements', 'theme'),
                'name'   => 'subscription_desc',
                'viewer' => [
                    'directory' => $path . '/subscription-desc',
                ],
            ],
            'subscription-banner'   => [
                'title'  => __('Bannière d\'inscription', 'theme'),
                'name'   => 'subscription_banner',
                'viewer' => [
                    'directory' => $path . '/subscription-banner',
                ],
            ]*/
            /**/

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Récupération de la devise utilisée.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string)$this->param('price.currency', 'EUR');
    }

    /**
     * Récupération de la position de la devise.
     *
     * @return string
     */
    public function getCurrencyPos(): string
    {
        return (string)$this->param('price.currency_pos', 'right');
    }

    /**
     * Récupération du nombre de décimal utilisée pour le calcul et l'affichage du prix.
     *
     * @return int
     */
    public function getPriceDecimals(): int
    {
        return (int)$this->param('price.num_decimals', 2);
    }

    /**
     * Récupération du séparateur de décimal utilisée pour l'affichage du prix.
     *
     * @return string
     */
    public function getPriceDecimalSeparator(): string
    {
        return ($separator = $this->param('price.decimal_sep')) ? stripslashes($separator) : '.';
    }

    /**
     * Récupération du format d'affichage du prix associé à la position de la devise.
     *
     * @return string
     */
    public function getPriceFormat(): string
    {
        switch ($this->getCurrencyPos()) {
            default:
            case 'left':
                $format = '%1$s%2$s';
                break;
            case 'right':
                $format = '%2$s%1$s';
                break;
            case 'left_space':
                $format = '%1$s&nbsp;%2$s';
                break;
            case 'right_space':
                $format = '%2$s&nbsp;%1$s';
                break;
        }

        return $format;
    }

    /**
     * Récupération de l'affichage de suffixe du prix.
     *
     * @return string
     */
    public function getPriceDisplaySuffix(): string
    {
        return (string)$this->param('price.display_suffix', '');
    }

    /**
     * Récupération du séparateur des milliers pour l'affichage du prix.
     *
     * @return string|null
     */
    public function getPriceThousandSeparator(): ?string
    {
        return stripslashes($this->param('price.thousand_sep', ''));
    }

    /**
     * Récupération de l'affichage des tarifs (boutique + panier + page de commande).
     *
     * @return string incl|excl
     */
    public function getTaxDisplay(): string
    {
        return $this->param('price.tax_display', 'incl') === 'excl' ? 'excl' : 'incl';
    }

    /**
     * Vérifie si la gestion de l'engagement est active.
     *
     * @return bool
     */
    public function isOfferDurationEnabled(): bool
    {
        return filter_var($this->param('offer.duration.enabled', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si la gestion du ré-engagement est actif.
     *
     * @return bool
     */
    public function isOfferRenewEnabled(): bool
    {
        return filter_var($this->param('offer.renew.enabled', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si les prix enregistré inclue la taxe.
     *
     * @return bool
     */
    public function isPricesIncludeTax()
    {
        return filter_var($this->param('price.include_tax'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si l'affichage des tarifs inclu la tax (boutique + panier + page de commande).
     *
     * @return bool
     */
    public function isTaxDisplayIncl(): bool
    {
        return $this->getTaxDisplay() === 'incl';
    }

    /**
     * Vérifie si la gestion de taxe est activée.
     *
     * @return bool
     */
    public function isTaxEnabled(): bool
    {
        return filter_var($this->param('price.calc_taxes', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Récupération de paramètre|Définition de paramètres|Instance du gestionnaire de paramètre.
     *
     * @param string|array|null $key Clé d'indice du paramètre à récupérer|Liste des paramètre à définir.
     * @param mixed $default Valeur de retour par défaut lorsque la clé d'indice est une chaine de caractère.
     *
     * @return mixed|ParamsBag
     */
    public function param($key = null, $default = null)
    {
        if (!$this->param instanceof ParamsBag) {
            $this->param = new ParamsBag();
        }

        if (is_string($key)) {
            return $this->param->get($key, $default);
        } elseif (is_array($key)) {
            return $this->param->set($key);
        } else {
            return $this->param;
        }
    }
}
