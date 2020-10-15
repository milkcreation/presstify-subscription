<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription;

use Illuminate\Support\Collection;
use tiFy\Support\{DateTime, ParamsBag};
use tiFy\Validation\Validator as v;
use tiFy\Wordpress\Contracts\Query\QueryUser as QueryUserContract;
use tiFy\Wordpress\Query\QueryUser;
use WP_User;

class SubscriptionCustomer extends ParamsBag
{
    use SubscriptionAwareTrait;

    /**
     * Instances des abonnements associés.
     * @return QuerySubscription[]|false|null
     */
    protected $subscriptions;

    /**
     * CONSTRUCTEUR.
     *
     * @param array|null $attrs
     *
     * @return void
     */
    public function __construct(?array $attrs = null)
    {
        $this->setSubscription(subscription());

        if (!is_null($attrs)) {
            $this->set($attrs)->parse();
        }
    }

    /**
     * Création d'une instance.
     *
     * @param string|int|QueryUserContract|null $id Identification du client email|user_id|query
     *
     * @return static
     */
    public static function create($id = null): self
    {
        if (is_numeric($id)) {
            return ($user = QueryUser::createFromId((int)$id)) ? static::createFromUser($user) : new static();
        } elseif ($id instanceof QueryUserContract) {
            return static::createFromUser($id);
        } elseif ($id instanceof WP_User) {
            return static::createFromUser(new QueryUser($id));
        } elseif (is_string($id) && v::email()->validate($id)) {
            return ($user = QueryUser::createFromEmail($id))
                ? static::createFromUser($user) : new static(['email' => $id]);
        } elseif (is_null($id)) {
            return ($user = QueryUser::createFromGlobal()) ? static::createFromUser($user) : new static();
        } else {
            return new static();
        }
    }

    /**
     * Création d'une instance basée sur un utilisateur.
     *
     * @param QueryUserContract $user
     *
     * @return static
     */
    public static function createFromUser(QueryUserContract $user)
    {
        return new static(['email' => $user->getEmail(), 'id' => $user->getId()]);
    }

    /**
     * Vérifie si le client est habilité à souscrire un abonnement.
     *
     * @return bool
     */
    public function canSubscribe(): bool
    {
        if (($exists = $this->getSubscription()) && !$exists->isRenewable()) {
            return false;
        }

        return true;
    }

    /**
     * Récupération de l'email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->get('email') ?: '';
    }

    /**
     * Récupération du nom d'affichage.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->get('display_name') ?: '';
    }

    /**
     * Récupération de l'identifiant de qualification de l'utilisateur associé.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->get('id') ?: 0;
    }

    /**
     * Récupération de l'abonnement renouvelable.
     *
     * @return QuerySubscription|null
     */
    public function getRenewableSubscription(): ?QuerySubscription
    {
        $subscriptions = $this->getSubscriptions();

        return (new Collection($subscriptions))->sortKeysDesc()->first(function (QuerySubscription $item) {
            return $item->isRenewable() && $item->getEndDate();
        });
    }

    /**
     * Récupération de l'abonnement courant.
     *
     * @return QuerySubscription|null
     */
    public function getSubscription(): ?QuerySubscription
    {
        $subscriptions = $this->getSubscriptions();

        return !empty($subscriptions) ? reset($subscriptions) : null;
    }

    /**
     * Récupération de la liste des abonnements courants.
     *
     * @return QuerySubscription[]|array
     */
    public function getSubscriptions()
    {
        if (is_null($this->subscriptions)) {
            /** @var QuerySubscription[]|array $subscriptions */
            $this->subscriptions = QuerySubscription::fetchFromArgs([
                'meta_key'   => '_end_date',
                'meta_type'  => 'DATETIME',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key'   => $this->getId() ? '_customer_id': '_customer_email',
                        'value' => $this->getId() ?: $this->getEmail(),
                    ],
                    [
                        'key'     => '_end_date',
                        'value'   => DateTime::now(),
                        'compare' => '>=',
                        'type'    => 'DATETIME',
                    ],
                ],
                'orderby'    => ['meta_value' => 'ASC', 'ID' => 'DESC'],
            ]);
        }

        return $this->subscriptions;
    }

    /**
     * Instance de l'utilisateur associé.
     *
     * @return QueryUserContract|null
     */
    public function getUser(): ?QueryUserContract
    {
        return ($id = $this->getId()) ? QueryUser::createFromId($id) : null;
    }
}