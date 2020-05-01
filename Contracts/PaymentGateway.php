<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Contracts;

use tiFy\Contracts\Support\ParamsBag;
use tiFy\Plugins\Subscription\Order\QueryOrder;
use tiFy\Plugins\Subscription\SubscriptionAwareTrait;

/**
 * @mixin SubscriptionAwareTrait
 */
interface PaymentGateway
{
    /**
     * Initialisation.
     *
     * @return static
     */
    public function boot(): PaymentGateway;

    /**
     * Récupération du paiement de la commande.
     *
     * @param array $params Paramètres d'enregistrement du paiement de la commande. transaction_id && date_paid
     *
     * @return void
     */
    public function capturePayment(array $params = []): void;

    /**
     * Liste des paramètres de configuration par défaut.
     *
     * @return array
     */
    public function defaults(): array;

    /**
     * Récupération du formulaire de paiement.
     *
     * @return string
     */
    public function getPaymentForm(): string;

    /**
     * Récupération de la commande associée.
     *
     * @return QueryOrder|null
     */
    public function getOrder(): ?QueryOrder;

    /**
     * Traitement d'un paiement en échec.
     *
     * @return void
     */
    public function handleFailed(): void;

    /**
     * Traitement d'un paiement annulé.
     *
     * @return void
     */
    public function handleCancelled():void;

    /**
     * Traitement d'un paiement réussi.
     *
     * @return void
     */
    public function handleSuccessed(): void;

    /**
     * Traitement de la notification instantanée de fin de paiement.
     *
     * @return void
     */
    public function handleIpn(): void;

    /**
     * Traitement d'un paiement en attente de réglement.
     *
     * @return void
     */
    public function handlePending(): void;

    /**
     * Vérification d'activation de la plateforme de paiement.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Récupération de paramètre|Définition de paramètres|Instance du gestionnaire de paramètre.
     *
     * @param string|array|null $key Clé d'indice du paramètre à récupérer|Liste des paramètre à définir.
     * @param mixed $default Valeur de retour par défaut lorsque la clé d'indice est une chaine de caractère.
     *
     * @return mixed|ParamsBag
     */
    public function params($key = null, $default = null);

    /**
     * Définition de la liste des paramètres de configuration.
     *
     * @param array $params
     *
     * @return static
     */
    public function setParams(array $params): PaymentGateway;

    /**
     * Définition de la commande associée.
     *
     * @param QueryOrder $order
     *
     * @return static
     */
    public function setOrder(QueryOrder $order): PaymentGateway;
}