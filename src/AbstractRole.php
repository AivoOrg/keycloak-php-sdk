<?php

declare(strict_types=1);

namespace Keycloak;

use JsonSerializable;

abstract class AbstractRole implements JsonSerializable, JsonDeserializable
{
    public string $id;
    public string $name;
    public ?string $description;
    public bool $composite;
    public bool $clientRole;

    public function __construct(
        string $id,
        string $name,
        ?string $description,
        bool $composite,
        bool $clientRole
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->composite = $composite;
        $this->clientRole = $clientRole;
    }

    public function jsonSerialize(): object
    {
        return $this;
    }

    /**
     * @param string|array $json
     * @return mixed Should always return an instance of the class that implements this interface.
     */
    abstract public static function fromJson($json): object;
}
