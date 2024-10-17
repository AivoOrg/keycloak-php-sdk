<?php

namespace Keycloak\Realm\Entity;

use Keycloak\AbstractRole;

class Role extends AbstractRole
{
    public static function fromJson($json): self
    {
        $arr = is_array($json) ? $json : json_decode($json, true);
        return new self(
            $arr['id'],
            $arr['name'],
            $arr['description'] ?? null,
            $arr['composite'] ?? false,
            $arr['clientRole']
        );
    }
}
