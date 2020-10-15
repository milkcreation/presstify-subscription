<?php declare(strict_types=1);

namespace tiFy\Plugins\Subscription\Order;

use tiFy\PostType\PostTypeStatus;

class OrderStatus extends PostTypeStatus
{
    /**
     * Alias de qualification.
     * @var string
     */
    protected $alias = '';

    /**
     * Récupération de l'alias de qualification.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Définition de l'alias de qualification.
     *
     * @param string $alias
     *
     * @return static
     */
    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}