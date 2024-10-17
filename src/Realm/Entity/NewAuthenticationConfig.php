<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;

class NewAuthenticationConfig implements JsonSerializable
{
    /** @var string */
    public $alias;
    /** @var array */
    public $config;

    public function __construct(
        string $alias,
        array $config
    ) {
        $this->alias = $alias;
        $this->config = $config;
    }

    public function jsonSerialize(): self
    {
        return $this;
    }
}
