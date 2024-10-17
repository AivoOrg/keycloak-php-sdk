<?php

namespace App\Tests;

use Keycloak\KeycloakClient;

class TestClient
{
    public static function createClient(): KeycloakClient
    {
        return new KeycloakClient(
            $_SERVER['KC_CLIENT_ID'],
            $_SERVER['KC_CLIENT_SECRET'],
            $_SERVER['KC_REALM'],
            $_SERVER['KC_URL'],
            'master'
        );
    }
}

$client = TestClient::createClient();
