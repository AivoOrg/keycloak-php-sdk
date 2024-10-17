<?php

/*
 * Use this bootstrap when you have legit Keycloak URL and credentials to test with.
 */

declare(strict_types=1);

use App\Tests\TestClient;
use Keycloak\Realm\RealmApi;

require __DIR__ . '/bootstrap.php';

$client = TestClient::createClient();
$realmApi = new RealmApi($client);
$existingRealm = $realmApi->find();

if ($existingRealm !== null) {
    $client->sendRequest('DELETE', '');
}

$realm = $_SERVER['KC_REALM'];
$client->sendRealmlessRequest('POST', '', [
    'enabled' => true,
    'id' => $realm,
    'realm' => $realm
]);
