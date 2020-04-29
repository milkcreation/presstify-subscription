<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

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
     * Initialisation.
     *
     * @return static
     */
    public function boot(): self
    {
        if (!$this->booted) {
            // OPTIONS
            $optionPage = Option::registerPage('subscription-settings', [
                'admin_menu' => [
                    'menu_title'  => __('Réglages', 'theme'),
                    'parent_slug' => 'subscription',
                ],
            ]);

            $optionPage->registerSettings([
                'currency_pos',
                'price_thousand_sep',
                'price_decimal_sep',
                'price_num_decimals',
                'calc_taxes',
                'prices_include_tax',
                'tax_display',
                'price_display_suffix',
                'custom_price_display_suffix',
            ]);

            // METABOXES
            $path = get_template_directory() . '/views/admin/metabox/options';
            Metabox::stack('subscription-settings@options', 'tab', [
                'subscription-prices' => [
                    'title'  => __('Tarification', 'theme'),
                    'viewer' => [
                        'directory' => $path . '/subscription-prices',
                    ],
                ],
                'subscription-desc'   => [
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
                ],
            ]);

            $this->booted = true;
        }

        return $this;
    }

    /**
     * Récupération de la devise utilisé par ma boutique.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return (string)get_option('currency', 'EUR');
    }

    /**
     * Récupération du nombre de décimal utilisée pour le calcul et l'affichage du prix.
     *
     * @return int
     */
    public function getPriceDecimals(): int
    {
        return absint(get_option('price_num_decimals', 2));
    }

    /**
     * Récupération du séparateur de décimal utilisée pour l'affichage du prix.
     *
     * @return string
     */
    public function getPriceDecimalSeparator(): string
    {
        return ($separator = get_option('price_decimal_sep')) ? stripslashes($separator) : '.';
    }

    /**
     * Récupération du format d'affichage du prix associé à la position de la devise.
     *
     * @return string
     */
    public function getPriceFormat(): string
    {
        switch (get_option('currency_pos')) {
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
        return (string)get_option('price_display_suffix', '');
    }

    /**
     * Récupération du séparateur des milliers pour l'affichage du prix.
     *
     * @return string|null
     */
    public function getPriceThousandSeparator(): ?string
    {
        return stripslashes(get_option('price_thousand_sep'));
    }

    /**
     * Récupération de l'affichage des tarifs (boutique + panier + page de commande).
     *
     * @return string incl|excl
     */
    public function getTaxDisplay(): string
    {
        return get_option('tax_display', 'incl') === 'excl' ? 'excl': 'incl';
    }

    /**
     * Vérifie si les prix enregistré inclue la taxe.
     *
     * @return bool
     */
    public function isPricesIncludeTax()
    {
        return static::isTaxEnabled() && filter_var(get_option('prices_include_tax'), FILTER_VALIDATE_BOOLEAN);
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
        return filter_var(get_option('calc_taxes', 'on'), FILTER_VALIDATE_BOOLEAN);
    }
}
