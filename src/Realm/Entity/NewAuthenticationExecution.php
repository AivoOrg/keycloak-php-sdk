<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;

class NewAuthenticationExecution implements JsonSerializable
{
    /** @var string */
    public $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    public function jsonSerialize(): self
    {
        return $this;
    }
}
