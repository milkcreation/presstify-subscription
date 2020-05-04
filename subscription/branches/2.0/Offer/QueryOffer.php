<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Offer;

use tiFy\Plugins\Subscription\SubscriptionAwareTrait;
use tiFy\Wordpress\Query\QueryPost as BaseQueryPost;
use WP_Post;

class QueryOffer extends BaseQueryPost
{
    use SubscriptionAwareTrait;

    /**
     * @inheritDoc
     */
    protected static $postType = 'subscription-offer';

    /**
     * CONSTRUCTEUR.
     *
     * @param WP_Post|null $wp_post Instance de post Wordpress.
     *
     * @return void
     */
    public function __construct(?WP_Post $wp_post = null)
    {
        $this->setSubscription(subscription());

        parent::__construct($wp_post);
    }

    /**
     * Récupération du prix affiché (Avec ou sans taxe).
     *
     * @param int|null $qty Nombre d'article
     *
     * @return float
     */
    public function getDisplayPrice(?int $qty = null): float
    {
        $qty = is_null($qty) ? 1 : $qty;

        return $this->subscription()->settings()->isTaxDisplayIncl()
            ? $this->getPriceWithTax($qty) : $this->getPriceWithoutTax($qty);
    }

    /**
     * Récupération de la durée de l'abonnement au format HTML.
     *
     * @return string|null
     */
    public function getDurationHtml(): ?string
    {
        if (!$length = $this->getDurationLength()) {
            return null;
        }

        switch ($this->getDurationUnity()) {
            default :
            case 'year' :
                return sprintf(_n('%d an', '%d ans', $length, 'tify'), $length);
                break;
            case 'month' :
                return sprintf(_n('%d mois', '%d mois', $length, 'tify'), $length);
                break;
            case 'day' :
                return sprintf(_n('%d jour', '%d jours', $length, 'tify'), $length);
                break;
        }
    }

    /**
     * Récupération de la durée de l'abonnement.
     *
     * @return int
     */
    public function getDurationLength(): int
    {
        return (int)$this->getMetaSingle('_duration_length', 0);
    }

    /**
     * Récupération de l'unité de durée de l'abonnement.
     *
     * @return string
     */
    public function getDurationUnity(): string
    {
        return $this->getMetaSingle('_duration_unity', 'year');
    }

    /**
     * Récupération de l'intitulé de qualification.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return (string)$this->getMetaSingle('_label', $this->getTitle());
    }

    /**
     * Récupération du prix enregistré.
     *
     * @return float
     */
    public function getPrice(): float
    {
        return max(0.0, (float)$this->getMetaSingle('_price', 0));
    }

    /**
     * Récupération de l'affichage du prix de vente au format HTML.
     *
     * @param int|null $qty Nombre d'article
     * @param array $args
     *
     * @return string
     */
    public function getPriceHtml(?int $qty = null, $args = []): string
    {
        if (in_array($this->subscription()->settings()->getPriceDisplaySuffix(), ['auto', 'auto_tax', 'incl', 'excl'])) {
            $args = array_merge([
                'tax_label' => $this->getTaxLabel()
            ], $args);
        }

        return $this->subscription()->functions()->displayPrice($this->getDisplayPrice($qty), $args) . $this->getPriceSuffixHtml();
    }

    /**
     * Récupération de l'affichage du prix de vente au format HTML.
     *
     * @param int|null $qty Nombre d'article
     *
     * @return string
     */
    public function getPriceSuffixHtml(?int $qty = null): string
    {
        $output = '';
        if ($suffix = $this->subscription()->settings()->getPriceDisplaySuffix()) {
            switch($suffix) {
                default :
                case 'none':
                case 'auto' :
                    break;
                case 'auto_tax' :
                    if ($this->subscription()->settings()->isTaxEnabled()) {
                        if ($this->subscription()->settings()->isTaxDisplayIncl()) {
                            $price = $this->getPriceWithoutTax($qty);
                            $tax_label = $this->getTaxLabel(false);
                        } else {
                            $price = $this->getPriceWithTax($qty);
                            $tax_label = $this->getTaxLabel(true);
                        }

                        $output = $this->subscription()->functions()->displayPrice($price, compact('tax_label'));
                    }
                    break;
                case 'incl' :
                    $output = $this->subscription()->functions()->displayPrice(
                        $this->getPriceWithTax($qty), ['tax_label' => $this->getTaxLabel(true)]
                    );
                    break;
                case 'excl' :
                    $output = $this->subscription()->functions()->displayPrice(
                        $this->getPriceWithoutTax($qty), ['tax_label' => $this->getTaxLabel(false)]
                    );
                    break;
                case 'custom' :
                    $output = $this->subscription()->settings()->params('price.display_suffix', '');
                    break;
            }
        }

        return $output;
    }

    /**
     * Récupération du prix de vente incluant la taxe.
     *
     * @param int|null $qty Nombre d'article
     *
     * @return float
     */
    public function getPriceWithTax(?int $qty = null): float
    {
        $price = $this->getPrice();
        $qty = is_null($qty) ? 1 : $qty;
        $line_price = $price * $qty;
        $return_price = $line_price;

        if ($this->isTaxable() && !$this->subscription()->settings()->isPricesIncludeTax()) {
            $return_price = $line_price * (1 + $this->getTaxRate());
        }

        return round($return_price, $this->subscription()->settings()->getPriceDecimals());
    }

    /**
     * Récupération du prix de vente hors taxe.
     *
     * @param int|null $qty Nombre d'article
     *
     * @return float
     */
    public function getPriceWithoutTax(?int $qty = null): float
    {
        $price = $this->getPrice();
        $qty = is_null($qty) ? 1 : $qty;
        $line_price = $price * $qty;
        $return_price = $line_price;

        if ($this->isTaxable() && $this->subscription()->settings()->isPricesIncludeTax()) {
            $return_price = $line_price / (1 + $this->getTaxRate());
        }

        return round($return_price, $this->subscription()->settings()->getPriceDecimals());
    }

    /**
     * Récupération de la TVA.
     *
     * @param int|null $qty Nombre d'article
     *
     * @return float
     */
    public function getPriceTax(?int $qty = null): float
    {
        if ($this->isTaxable()) {
            $price = $this->getPriceWithoutTax();
            $qty = is_null($qty) ? 1 : $qty;
            $line_price = $price * $qty;

            return round($line_price * $this->getTaxRate(), $this->subscription()->settings()->getPriceDecimals());
        } else {
            return 0;
        }
    }

    /**
     * Récupération du nombre de jours de la période le ré-engagement.
     *
     * @return int
     */
    public function getRenewableDays(): int
    {
        return (int)$this->getMetaSingle('_renewable_days', 0);
    }

    /**
     * Récupération de l'unité de gestion de stock (EAN13, Réf ...).
     *
     * @return string
     */
    public function getSku(): string
    {
        return (string)$this->getMetaSingle('_sku', '');
    }

    /**
     * Récupération de la taxe produit.
     *
     * @return float
     */
    public function getTax(): float
    {
        return (float)$this->getMetaSingle('_tax', 0);
    }

    /**
     * Récupération de la taxe produit.
     *
     * @param bool|null $incl Indicateur de taxe incluse.
     *
     * @return string
     */
    public function getTaxLabel(?bool $incl = null): string
    {
        $with = __('TTC', 'tify');
        $without = __('HT', 'tify');

        if (is_null($incl)) {
            return $this->subscription()->settings()->isTaxDisplayIncl() ? $with : $without;
        } else {
            return $incl ? $with : $without;
        }
    }

    /**
     * Récupération de la taxe produit.
     *
     * @return float
     */
    public function getTaxRate(): float
    {
        return ($tax = $this->getTax()) ? $tax / 100 : 0;
    }


    /**
     * Vérifie si le produit peut être commandé.
     *
     * @return bool
     */
    public function isPurchasable(): bool
    {
        return true;
    }

    /**
     * Vérification de l'envoi d'un mail de notification lors de la période de ré-engagement.
     *
     * @return bool
     */
    public function isRenewNotify(): bool
    {
        return filter_var($this->getMetaSingle('_renew_notification'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si le produit est soumis à une taxe.
     *
     * @return bool
     */
    public function isTaxable(): bool
    {
        return $this->subscription()->settings()->isTaxEnabled() && !!$this->getTax();
    }
}