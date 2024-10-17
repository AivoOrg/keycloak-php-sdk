<?php

namespace App\Tests;

use Keycloak\Client\ClientApi;
use Keycloak\Client\Entity\Client;
use Keycloak\User\Entity\CompositeRole;
use Keycloak\User\Entity\User;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    protected ClientApi $clientApi;

    protected function setUp(): void
    {
        global $client;
        $this->clientApi = new ClientApi($client);
    }

    public function testFindAll(): void
    {
        $allClients = $this->clientApi->findAll();
        $this->assertNotEmpty($allClients);
    }

    public function testFind(): void
    {
        // account is a standard client that should always exist
        $client = $this->clientApi->findByClientId('account');
        $this->assertInstanceOf(Client::class, $client);
        $this->assertInstanceOf(Client::class, $this->clientApi->find($client->id));
    }

    public function testFindNothing(): void
    {
        $this->assertNull($this->clientApi->findByClientId('blipblop'));
        $this->assertNull($this->clientApi->find('blipblop'));
    }

    public function testCreate(): void
    {
        $id = $this->clientApi->create('test-client');
        $this->assertNotEmpty($id);
        $this->assertInstanceOf(Client::class, $this->clientApi->find($id));
    }

    public function testUpdate(): void
    {
        $client = $this->clientApi->findByClientId('test-client');
        $this->assertTrue($client->enabled);

        $client->enabled = false;
        $this->clientApi->update($client);

        $client = $this->clientApi->find($client->id);
        $this->assertFalse($client->enabled);
    }

    public function testDelete(): void
    {
        $client = $this->clientApi->findByClientId('test-client');
        $this->clientApi->delete($client->id);

        $this->assertNull($this->clientApi->find($client->id));
    }

    public function testServiceAccountUser(): void
    {
        $id = $this->clientApi->create('service-account-test');
        $client = $this->clientApi->find($id);

        $client->publicClient = false;
        $client->serviceAccountsEnabled = true;
        $this->clientApi->update($client);

        $serviceAccount = $this->clientApi->getServiceAccountUser($id);
        $this->assertInstanceOf(User::class, $serviceAccount);

        $this->clientApi->delete($id);
    }

    public function testClientSecret(): void
    {
        $id = $this->clientApi->create('client-secret-test');
        $client = $this->clientApi->find($id);

        $client->publicClient = false;
        $client->directAccessGrantsEnabled = true;
        $this->clientApi->update($client);

        $secret = $this->clientApi->getClientSecret($id);
        $this->assertNotEmpty($secret);

        $newSecret = $this->clientApi->generateClientSecret($id);
        $this->assertNotSame($secret, $newSecret);

        $this->clientApi->delete($id);
    }

    public function testGetCompositeRolesWithPermissions(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $compositeRoles = $this->clientApi->getCompositeRolesWithPermissions($client->id);
        $this->assertNotEmpty($compositeRoles);
        $this->assertInstanceOf(CompositeRole::class, $compositeRoles[0]);
    }
}
