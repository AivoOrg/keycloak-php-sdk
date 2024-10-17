<?php

namespace App\Tests;

use Keycloak\Client\ClientApi;
use Keycloak\Client\Entity\Client;
use Keycloak\Exception\KeycloakException;
use Keycloak\Client\Entity\Role;
use Keycloak\Realm\RealmApi;
use PHPUnit\Framework\TestCase;

class ClientRoleTest extends TestCase
{
    protected ClientApi $clientApi;
    protected RealmApi $realmApi;
    protected Role $role;
    protected Role $permission1;
    protected Role $permission2;

    protected function setUp(): void
    {
        global $client;
        $this->clientApi = new ClientApi($client);
        $this->realmApi = new RealmApi($client);
        $this->role = new Role(
            'roleId',
            'role',
            'description',
            true,
            true
        );
        $this->permission1 = new Role(
            'permissionId',
            'permission1',
            'description',
            false,
            false
        );
        $this->permission2 = new Role(
            'permissionId2',
            'permission2',
            'description',
            false,
            false
        );
    }

    public function testCreateRole(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        self::assertInstanceOf(Client::class, $client);
        $existingRole = $this->clientApi->getRole($this->role, $client->id);
        if ($existingRole !== null) {
            $this->clientApi->deleteRole($this->role->name, $client->id);
        }

        $roleId = $this->clientApi->createRole($this->role, $client->id);
        self::assertNotEmpty($roleId);
    }

    public function testUpdateRole(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        self::assertInstanceOf(Client::class, $client);
        $this->clientApi->updateRole($this->role, $client->id);
        $this->role = $this->clientApi->getRole($this->role, $client->id);
        self::assertEquals('role', $this->role->name);
    }

    public function testCreatePermissions(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $permission1 = $this->clientApi->createRole($this->permission1, $client->id);
        self::assertNotEmpty($permission1);
        $permission2 = $this->clientApi->createRole($this->permission2, $client->id);
        self::assertNotEmpty($permission2);
    }

    public function testAddPermissionsToRole(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $this->role->name = 'role';
        $role = $this->clientApi->getRole($this->role, $client->id);
        $permissions = [
            $this->clientApi->getRole($this->permission1, $client->id),
            $this->clientApi->getRole($this->permission2, $client->id)
        ];
        $this->clientApi->addPermissions($role->name, $client->id, $permissions);
        $this->assertEquals('role', $this->role->name);
    }

    public function testDeletePermissionsByRoleId(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $this->role->name = 'role';
        $role = $this->clientApi->getRole($this->role, $client->id);

        $permissionsBeforeDeletion = $this->clientApi->getCompositesFromRole($client->id, $role->name);
        $this->assertCount(2, $permissionsBeforeDeletion);

        $permission = $this->clientApi->getRole($this->permission1, $client->id);
        $this->clientApi->deletePermissionsByRoleId($role->id, [$permission]);
        $permissionsAfterDeletion = $this->clientApi->getCompositesFromRole($client->id, $role->name);
        $this->assertCount(1, $permissionsAfterDeletion);
    }

    public function testAddPermissionsByRoleId(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $role = $this->clientApi->getRole($this->role, $client->id);

        $permissionsBeforeAddition = $this->clientApi->getCompositesFromRole($client->id, $role->name);
        $this->assertCount(1, $permissionsBeforeAddition);

        $permission = $this->realmApi->getRoles()[0];
        $this->clientApi->addPermissionsByRoleId($role->id, [$permission]);

        $permissionsAfterAddition = $this->clientApi->getCompositesFromRole($client->id, $role->name);
        $this->assertCount(2, $permissionsAfterAddition);
    }

    public function testDeletePermissions(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $this->clientApi->deleteRole($this->permission1->name, $client->id);
        $deletedPermission1 = $this->clientApi->getRole($this->permission1, $client->id);
        $this->clientApi->deleteRole($this->permission2->name, $client->id);
        $deletedPermission2 = $this->clientApi->getRole($this->permission2, $client->id);
        $this->assertNull($deletedPermission1);
        $this->assertNull($deletedPermission2);
    }

    public function testGetRoles(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $this->assertInstanceOf(Client::class, $client);
        $clientRoles = $this->clientApi->getRoles($client->id);
        $this->assertNotEmpty($clientRoles);

        $this->expectException(KeycloakException::class);
        $this->clientApi->getRoles('blipblop');
    }

    public function testGetCompositeRoles(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $compositeRoles = $this->clientApi->getCompositeRoles($client->id);
        $this->assertNotEmpty($compositeRoles);
        $this->assertInstanceOf(Role::class, $compositeRoles[0]);
    }

    public function testGetCompositesFromRole(): void
    {
        $client = $this->clientApi->findByClientId('realm-management');
        $compositeRoles = $this->clientApi->getCompositesFromRole($client->id, 'realm-admin');
        $this->assertNotEmpty($compositeRoles);
        $this->assertInstanceOf(Role::class, $compositeRoles[0]);
    }
}
