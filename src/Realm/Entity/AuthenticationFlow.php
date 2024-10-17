<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;
use Keycloak\JsonDeserializable;

class AuthenticationFlow implements JsonSerializable, JsonDeserializable
{
    /** @var string */
    public $id;
    /** @var string */
    public $alias;
    /** @var string */
    public $description;
    /** @var string */
    public $providerId;
    /** @var bool */
    public $topLevel;
    /** @var bool */
    public $buildIn;
    /** @var array */
    public $authenticationExecutions;

    public function __construct(
        string $id,
        string $alias,
        string $description,
        string $providerId,
        bool $topLevel,
        bool $buildIn,
        array $authenticationExecutions
    ) {
        $this->id = $id;
        $this->alias = $alias;
        $this->description = $description;
        $this->providerId = $providerId;
        $this->topLevel = $topLevel;
        $this->buildIn = $buildIn;
        $this->authenticationExecutions = $authenticationExecutions;
    }

    public function jsonSerialize(): self
    {
        return $this;
    }

    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['alias'],
            $arr['description'],
            $arr['providerId'] ?? '',
            $arr['topLevel'] ?? false,
            $arr['buildIn'] ?? false,
            $arr['authenticationExecutions'] ?? []
        );
    }
}
