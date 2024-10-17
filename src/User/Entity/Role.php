<?php

namespace Keycloak\User\Entity;

use Keycloak\AbstractRole;

class Role extends AbstractRole
{
    public ?string $clientId;

    public function __construct(
        string $id,
        string $name,
        ?string $description,
        bool $composite,
        bool $clientRole,
        ?string $clientId
    ) {
        parent::__construct($id, $name, $description, $composite, $clientRole);
        $this->clientId = $clientId;
    }

    /**
     * @param string|array $json
     * @return mixed Should always return an instance of the class that implements this interface.
     */
    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['name'],
            $arr['description'] ?? null,
            $arr['composite'] ?? false,
            $arr['clientRole'],
            $arr['clientId'] ?? null
        );
    }
}
