<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;

class NewAuthenticationFlow implements JsonSerializable
{
    /** @var string */
    public $alias;
    /** @var string */
    public $description;
    /** @var string */
    public $providerId;
    /** @var bool */
    public $topLevel;
    /** @var array */
    public $authenticationExecutions;

    public function __construct(
        string $alias,
        string $description,
        string $providerId,
        bool $topLevel,
        array $authenticationExecutions
    ) {
        $this->alias = $alias;
        $this->description = $description;
        $this->providerId = $providerId;
        $this->topLevel = $topLevel;
        $this->authenticationExecutions = $authenticationExecutions;
    }

    public function jsonSerialize(): self
    {
        return $this;
    }
}
