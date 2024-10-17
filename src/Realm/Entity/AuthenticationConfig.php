<?php

declare(strict_types=1);

namespace Keycloak\Realm\Entity;

use JsonSerializable;
use Keycloak\JsonDeserializable;

class AuthenticationConfig implements JsonSerializable, JsonDeserializable
{
    /** @var string */
    public $id;
    /** @var string */
    public $alias;
    /** @var array */
    public $config;

    public function __construct(
        string $id,
        string $alias,
        array $config
    ) {
        $this->id = $id;
        $this->alias = $alias;
        $this->config = $config;
    }


    public function jsonSerialize(): self
    {
        return $this;
    }

    public static function fromJson($json)
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['alias'],
            $arr['config']
        );
    }
}
