<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use tiFy\Mail\Mailer;
use tiFy\Support\{Arr, ParamsBag};
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
    protected $params;

    /**
     * Adresse de messagerie par défault d'expédition des email transactionnels.
     * @var array|null
     */
    protected $defaultEmail;

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
                    'menu_title'  => __('Réglages', 'tify'),
                    'parent_slug' => 'subscription',
                    'position'    => 4,
                ],
            ]);

            $optionPage->registerSettings([
                'subscription_price',
                'subscription_offer',
            ]);
            /**/

            $this->params([
                'form'      => array_merge(
                    $this->subscription()->config('settings.form', []),
                    get_option('subscription_form') ?: []
                ),
                'mail'       => [
                    'order_confirmation' => array_merge(
                        $this->subscription()->config('settings.mail.order_confirmation', []),
                        get_option('subscription_mail_order_confirmation') ?: []
                    ),
                    'order_notification' => array_merge(
                        $this->subscription()->config('settings.mail.order_notification', []),
                        get_option('subscription_mail_order_notification') ?: []
                    ),
                    'renew_notify'       => array_merge(
                        $this->subscription()->config('settings.mail.renew_notify', []),
                        get_option('subscription_mail_renew_notify') ?: []
                    ),
                ],
                'legal_info' => array_merge(
                    $this->subscription()->config('settings.legal_info', []),
                    get_option('subscription_legal_info') ?: []
                ),
                'offer'      => array_merge(
                    $this->subscription()->config('settings.offer', []),
                    get_option('subscription_offer') ?: []
                ),
                'price'      => array_merge(
                    $this->subscription()->config('settings.price', []),
                    get_option('subscription_price') ?: []
                ),
            ]);

            /* METABOXES */
            $path = dirname(__FILE__) . '/Resources/views/admin/metabox/options';
            // -- Tarification
            Metabox::add('subscription-price', [
                'name'   => 'subscription_price',
                'title'  => __('Tarification', 'tify'),
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
                'title'  => __('Offres', 'tify'),
                'viewer' => [
                    'directory' => $path . '/offer',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            // -- Emails
            Metabox::add('subscription-mail', [
                'title' => __('Emails', 'tify'),
            ])->setScreen('subscription-settings@options')->setContext('tab');

            // --- Confirmation d'abonnement
            Metabox::add('subscription-mail_order_confirmation', [
                'name'   => 'subscription_mail_order_confirmation',
                'parent' => 'subscription-mail',
                'title'  => __('Confirmation de commande', 'tify'),
                'value'  => get_option('subscription_mail_order_confirmation') ?: [],
                'viewer' => [
                    'directory' => $path . '/mail/order-confirmation',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            register_setting('subscription-settings', 'subscription_mail_order_confirmation', function ($value) {
                $sender = $value['sender'] ?? null;

                if (!empty($sender['email']) && !is_email($sender['email'])) {
                    add_settings_error(
                        'subscription-settings',
                        'sender-email_format',
                        __('Email de l\'expéditeur de confirmation de commande non valide.', 'tify'),
                    );
                }

                return $value;
            });

            // --- Notification d'abonnement
            Metabox::add('subscription-mail_order_notification', [
                'name'   => 'subscription_mail_order_notification',
                'parent' => 'subscription-mail',
                'title'  => __('Notification de commande', 'tify'),
                'value'  => get_option('subscription_mail_order_notification') ?: [],
                'viewer' => [
                    'directory' => $path . '/mail/order-notification',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            register_setting('subscription-settings', 'subscription_mail_order_notification', function ($value) {
                $sender = $value['sender'] ?? null;

                if (!empty($sender['email']) && !is_email($sender['email'])) {
                    add_settings_error(
                        'subscription-settings',
                        'sender-email_format',
                        __('Email de l\'expéditeur de la notification non valide.', 'tify'),
                    );
                }

                $recipients = $value['recipients'] ?? null;

                if ($recipients) {
                    foreach ($recipients as $recipient => $recip) {
                        if (empty($recip['email'])) {
                            add_settings_error(
                                'subscription-settings',
                                $recipient . '-email_empty',
                                __('L\'email du destinataire de la notification de commande ne peut être vide.', 'tify')
                            );
                        } elseif (!is_email($recip['email'])) {
                            add_settings_error(
                                'subscription-settings',
                                $recipient . '-email_format',
                                __('Email du destinataire de la notification de commande non valide', 'tify')
                            );
                        }
                    }
                }

                return $value;
            });

            // --- Invite de ré-engagement
            Metabox::add('subscription-mail_renew_notify', [
                'name'   => 'subscription_mail_renew_notify',
                'parent' => 'subscription-mail',
                'title'  => __('Invite de ré-engagement', 'tify'),
                'value'  => get_option('subscription_mail_renew_notify') ?: [],
                'viewer' => [
                    'directory' => $path . '/mail/renew-notify',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

            register_setting('subscription-settings', 'subscription_mail_renew_notify', function ($value) {
                $sender = $value['sender'] ?? null;

                if (!empty($sender['email']) && !is_email($sender['email'])) {
                    add_settings_error(
                        'subscription-settings',
                        'sender-email_format',
                        __('Email de l\'expéditeur de l\'invite de ré-engagement non valide.', 'tify'),
                    );
                }

                return $value;
            });

            // Informations légales
            Metabox::add('subscription-legal_info', [
                'name'   => 'subscription_legal_info',
                'title'  => __('Information légales', 'tify'),
                'value'  => get_option('subscription_legal_info') ?: [],
                'viewer' => [
                    'directory' => $path . '/legal-info',
                ],
            ])->setScreen('subscription-settings@options')->setContext('tab')
                ->setHandler(function ($box) {
                    $box->set('settings', $this);
                });

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
        return (string)$this->params('price.currency', 'EUR');
    }

    /**
     * Récupération de la position de la devise.
     *
     * @return string
     */
    public function getCurrencyPos(): string
    {
        return (string)$this->params('price.currency_pos', 'right');
    }

    /**
     * Récupération de l'adresse de messagerie par défaut des transactions.
     *
     * @return array
     */
    public function getDefaultEmail(): array
    {
        if (is_null($this->defaultEmail)) {
            $email = get_option('admin_email');
            $default[] = $email;

            if ($user = get_user_by('email', $email)) {
                $default[] = $user->display_name;
            }

            $this->defaultEmail = $default;
        }

        return $this->defaultEmail;
    }

    /**
     * Récupération de la durée d'engagement des offres.
     *
     * @return int
     */
    public function getOfferLimitedLength(): int
    {
        return (int)$this->params('offer.limited.length', 1);
    }

    /**
     * Récupération de l'unité de la durée d'engagement des offres.
     *
     * @return string
     */
    public function getOfferLimitedUnity(): string
    {
        return (string)$this->params('offer.limited.unity', 'year');
    }

    /**
     * Récupération du nombre de jour de permissions de ré-engagement des offres.
     *
     * @return int
     */
    public function getOfferRenewDays(): int
    {
        return (int)$this->params('offer.renew.days', 30);
    }

    /**
     * Récupération de l'expéditeur du mail de confirmation d'abonnement.
     *
     * @return array
     */
    public function getOrderConfirmationSender(): array
    {
        return ($v = Mailer::parseContact($this->params('mail.order_confirmation.sender')))
            ? current($v) : $this->getDefaultEmail();
    }

    /**
     * Récupération de l'expéditeur du mail de notification d'abonnement.
     *
     * @return array
     */
    public function getOrderNotificationSender(): array
    {
        return ($v = Mailer::parseContact($this->params('mail.order_notification.sender')))
            ? current($v) : $this->getDefaultEmail();
    }

    /**
     * Récupération du(es) destinataire(s) du mail de notification d'abonnement.
     *
     * @return array
     */
    public function getOrderNotificationRecipients(): array
    {
        return ($v = Mailer::parseContact(array_values($this->params('mail.order_notification.recipients', [])))
        ) ? $v : $this->getDefaultEmail();
    }

    /**
     * Récupération du nombre de décimal utilisée pour le calcul et l'affichage du prix.
     *
     * @return int
     */
    public function getPriceDecimals(): int
    {
        return (int)$this->params('price.num_decimals', 2);
    }

    /**
     * Récupération du séparateur de décimal utilisée pour l'affichage du prix.
     *
     * @return string
     */
    public function getPriceDecimalSeparator(): string
    {
        return ($separator = $this->params('price.decimal_sep')) ? Arr::stripslashes($separator) : '.';
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
        return (string)$this->params('price.display_suffix', '');
    }

    /**
     * Récupération du séparateur des milliers pour l'affichage du prix.
     *
     * @return string|null
     */
    public function getPriceThousandSeparator(): ?string
    {
        return Arr::stripslashes($this->params('price.thousand_sep', ''));
    }

    /**
     * Récupération de l'identifiant de qualification de la page de politique de confidentialité du site.
     *
     * @return int
     */
    public function getPrivacyPolicyPageId(): int
    {
        return (int)$this->params('legal_info.privacy_policy', get_option('wp_page_for_privacy_policy'));
    }

    /**
     * Nombre de jours avant l'expédition d'un mail d'invitation au ré-engagement d'abonnement.
     *
     * @return int
     */
    public function getRenewNotifyDays(): int
    {
        return (int)($this->params('mail.renew_notify.days', 0) ?: ceil($this->getOfferRenewDays() / 2));
    }

    /**
     * Récupération de l'expéditeur du mail d'invite de ré-engagement d'un abonné.
     *
     * @return array
     */
    public function getRenewNotifySender(): array
    {
        return ($v = Mailer::parseContact($this->params('mail.renew_notify.sender')))
            ? current($v) : $this->getDefaultEmail();
    }

    /**
     * Récupération de l'affichage des tarifs (boutique + panier + page de commande).
     *
     * @return string incl|excl
     */
    public function getTaxDisplay(): string
    {
        return $this->params('price.tax_display', 'incl') === 'excl' ? 'excl' : 'incl';
    }

    /**
     * Récupération de l'identifiant de qualification de la page de politique de confidentialité du site.
     *
     * @return int
     */
    public function getTermsOfUsePageId(): int
    {
        return (int)$this->params('legal_info.terms_of_use', 0);
    }

    /**
     * Vérifie si la gestion de l'engagement est active.
     *
     * @return bool
     */
    public function isOfferLimitedEnabled(): bool
    {
        return filter_var($this->params('offer.limited.enabled', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si la gestion du ré-engagement est actif.
     *
     * @return bool
     */
    public function isOfferRenewEnabled(): bool
    {
        return filter_var($this->params('offer.renew.enabled', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si la gestion d'un email de confirmation de commande est actif.
     *
     * @return bool
     */
    public function isOrderConfirmationEnabled(): bool
    {
        return filter_var($this->params('mail.order_confirmation.enabled', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si la gestion d'un email de confirmation de commande est actif.
     *
     * @return bool
     */
    public function isOrderNotificationEnabled(): bool
    {
        return filter_var($this->params('mail.order_notification.enabled', 'off'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si les prix enregistré inclue la taxe.
     *
     * @return bool
     */
    public function isPricesIncludeTax()
    {
        return filter_var($this->params('price.include_tax'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Vérifie si la gestion d'un email de ré-engagement est actif.
     *
     * @return bool
     */
    public function isRenewNotifyEnabled(): bool
    {
        return filter_var($this->params('mail.renew_notify.enabled', 'off'), FILTER_VALIDATE_BOOLEAN);
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
        return filter_var($this->params('price.calc_taxes', 'on'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Récupération de paramètre|Définition de paramètres|Instance du gestionnaire de paramètre.
     *
     * @param string|array|null $key Clé d'indice du paramètre à récupérer|Liste des paramètre à définir.
     * @param mixed $default Valeur de retour par défaut lorsque la clé d'indice est une chaine de caractère.
     *
     * @return mixed|ParamsBag
     */
    public function params($key = null, $default = null)
    {
        if (!$this->params instanceof ParamsBag) {
            $this->params = new ParamsBag();
        }

        if (is_string($key)) {
            return $this->params->get($key, $default);
        } elseif (is_array($key)) {
            return $this->params->set($key);
        } else {
            return $this->params;
        }
    }
}
