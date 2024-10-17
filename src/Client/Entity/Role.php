<?php

namespace Keycloak\Client\Entity;

use Keycloak\AbstractRole;

/**
 * Class Role
 * @package Keycloak\Client\Entity
 */
class Role extends AbstractRole
{
    /**
     * @param string|array $json
     * @return mixed Should always return an instance of the class that implements this interface.
     */
    public static function fromJson($json): self
    {
        $arr = is_array($json)
            ? $json
            : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['name'],
            $arr['description'] ?? null,
            $arr['composite'] ?? false,
            $arr['clientRole']
        );
    }
}
