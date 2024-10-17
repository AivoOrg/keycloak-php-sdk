<?php

namespace App\Tests;

use Keycloak\Exception\KeycloakException;
use Keycloak\User\UserApi;
use Keycloak\Client\ClientApi;
use Keycloak\User\Entity\Credential;
use Keycloak\User\Entity\Role;
use Keycloak\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Keycloak\User\Entity\NewUser;

/**
 * Class ApiTest
 * These tests are ran synchronously from top to bottom.
 * A user is created at the start and cleanup is done at the end.
 * This way we don't need any mocks and we can test with a real KC instance for higher accuracy.
 */
final class UserTest extends TestCase
{
    protected UserApi $userApi;
    protected ClientApi $clientApi;
    protected NewUser $user;

    protected function setUp(): void
    {
        global $client;
        $this->userApi = new UserApi($client);
        $this->clientApi = new ClientApi($client);
        $this->user = new NewUser(
            'php.unit',
            'php',
            'unit',
            'php.unit@example.com'
        );
    }

    public function testCreate(): void
    {
        $this->user->enabled = false;
        $userId = $this->userApi->create($this->user);
        $this->assertNotEmpty($userId);
    }

    public function testDuplicateCreate(): void
    {
        $this->expectException(KeycloakException::class);
        $this->userApi->create($this->user);
    }

    /**
     * Helper function to get the user.
     * Tests should not share state.
     * Therefor it is impossible to persist an ID between tests and this function is needed.
     * @return User|null
     */
    private function getUser(): ?User
    {
        $users = $this->userApi->findAll(['username' => $this->user->username, 'email' => $this->user->email]);
        if (empty($users)) {
            return null;
        }
        return array_pop($users);
    }

    public function testFind(): void
    {
        $user = $this->getUser();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testFindNothing(): void
    {
        $noUser = $this->userApi->find('blipblop');
        $this->assertNull($noUser);
    }

    public function testUpdate(): void
    {
        $user = $this->getUser();
        $this->assertFalse($user->enabled);

        $user->firstName = 'unit';
        $user->lastName = 'php';
        $user->enabled = true;
        $this->userApi->update($user);

        $updatedUser = $this->userApi->find($user->id);
        $this->assertEquals('unit', $updatedUser->firstName);
        $this->assertEquals('php', $updatedUser->lastName);
        $this->assertTrue($updatedUser->enabled);
    }

    public function testResetPassword(): void
    {
        $this->expectNotToPerformAssertions();

        $user = $this->getUser();
        $this->userApi->resetPassword($user->id, 'NewPassword123');
        $this->userApi->resetPassword($user->id, 'NewTempPassword123', true);
    }

    public function testAddAttribute(): void
    {
        $user = $this->getUser();

        $this->assertEmpty($user->attributes);
        $user->attributes['customer_code'] = ['KL113'];
        $this->userApi->update($user);

        $user = $this->getUser();
        $this->assertNotEmpty($user->attributes);
        $this->assertEquals($user->attributes['customer_code'][0], 'KL113');
    }

    public function testDeleteAttribute(): void
    {
        $user = $this->getUser();
        $this->assertEquals($user->attributes['customer_code'][0], 'KL113');
        $user->attributes = [];
        $this->userApi->update($user);

        $user = $this->getUser();
        $this->assertEmpty($user->attributes);
    }

    public function testRoles(): void
    {
        $user = $this->getUser();
        $roles = $this->userApi->getRoles($user->id);
        $this->assertCount(1, $roles);
        $this->assertEquals('default-roles-' . $_SERVER['KC_REALM'], $roles[0]->name);
    }

    public function testRealmRoles(): void
    {
        $user = $this->getUser();
        $roles = $this->userApi->getRealmRoles($user->id);
        $this->assertCount(1, $roles);
        $this->assertEquals('default-roles-' . $_SERVER['KC_REALM'], $roles[0]->name);
    }

    public function testAvailableRealmRoles(): void
    {
        $user = $this->getUser();
        $availableRoles = $this->userApi->getAvailableRealmRoles($user->id);
        $this->assertCount(2, $availableRoles);
    }

    public function testAddRealmRole(): void
    {
        $user = $this->getUser();
        $availableRoles = $this->userApi->getAvailableRealmRoles($user->id);

        $roleToAdd = null;
        foreach ($availableRoles as $role) {
            if ($role->name === 'offline_access') {
                $roleToAdd = $role;
                break;
            }
        }
        $this->userApi->addRealmRoles($user->id, [$roleToAdd]);
        $roles = $this->userApi->getRealmRoles($user->id);
        $this->assertCount(2, $roles);
    }

    public function testDeleteRealmRole(): void
    {
        $user = $this->getUser();
        $roles = $this->userApi->getRealmRoles($user->id);

        $roleToDelete = null;
        foreach ($roles as $role) {
            if ($role->name === 'offline_access') {
                $roleToDelete = $role;
                break;
            }
        }
        $this->userApi->deleteRealmRoles($user->id, [$roleToDelete]);
        $roles = $this->userApi->getRealmRoles($user->id);
        $this->assertCount(1, $roles);
    }

    public function testAddClientRole(): void
    {
        $user = $this->getUser();
        $client = $this->clientApi->findByClientId('realm-management');

        $availableRoles = $this->userApi->getAvailableClientRoles($user->id, $client->id);
        $viewClientsRole = null;
        foreach ($availableRoles as $role) {
            if ($role->name === 'view-clients') {
                $viewClientsRole = $role;
            }
        }
        $this->assertInstanceOf(Role::class, $viewClientsRole);

        $rolesBeforeAdd = $this->userApi->getRoles($user->id);
        $this->userApi->addClientRoles($user->id, $client->id, [$viewClientsRole]);

        $rolesAfterAdd = $this->userApi->getRoles($user->id);
        $this->assertGreaterThan(count($rolesBeforeAdd), count($rolesAfterAdd));

        $added = false;
        foreach ($rolesAfterAdd as $role) {
            if ($role->id === $viewClientsRole->id) {
                $added = true;
            }
        }
        $this->assertTrue($added);

        $availableRolesAfterAdd = $this->userApi->getAvailableClientRoles($user->id, $client->id);
        $this->assertLessThan(count($availableRoles), count($availableRolesAfterAdd));
    }

    public function testAddClientRoleWithMinimalInfo(): void
    {
        $user = $this->getUser();
        $client = $this->clientApi->findByClientId('realm-management');

        $availableRoles = $this->userApi->getAvailableClientRoles($user->id, $client->id);
        $viewClientsRole = null;
        foreach ($availableRoles as $role) {
            if ($role->name === 'view-users') {
                $viewClientsRole = ['id' => $role->id, 'name' => $role->name];
            }
        }

        $rolesBeforeAdd = $this->userApi->getRoles($user->id);
        $this->userApi->addClientRolesWithMinimalInfo($user->id, $client->id, $viewClientsRole);

        $rolesAfterAdd = $this->userApi->getRoles($user->id);
        $this->assertGreaterThan(count($rolesBeforeAdd), count($rolesAfterAdd));

        $added = false;
        foreach ($rolesAfterAdd as $role) {
            if ($role->id === $viewClientsRole['id']) {
                $added = true;
            }
        }
        $this->assertTrue($added);

        $availableRolesAfterAdd = $this->userApi->getAvailableClientRoles($user->id, $client->id);
        $this->assertLessThan(count($availableRoles), count($availableRolesAfterAdd));
    }

    public function testListClientRoles(): void
    {
        $user = $this->getUser();
        $client = $this->clientApi->findByClientId('realm-management');
        $clientRoles = $this->userApi->getClientRoles($user->id, $client->id);
        $this->assertNotEmpty($clientRoles);

        $client = $this->clientApi->findByClientId('realm-management');
        $availableRoles = $this->userApi->getAvailableClientRoles($user->id, $client->id);
        $this->assertNotEmpty($availableRoles);

        foreach (array_merge($clientRoles, $availableRoles) as $role) {
            $this->assertInstanceOf(Role::class, $role);
        }

        $this->expectException(KeycloakException::class);
        $this->userApi->getClientRoles($user->id, 'blipblop');
    }

    public function testDeleteClientRoles(): void
    {
        $user = $this->getUser();
        $client = $this->clientApi->findByClientId('realm-management');
        $roles = $this->userApi->getClientRoles($user->id, $client->id);

        $this->userApi->deleteClientRoles($user->id, $client->id, $roles);
        $this->assertEmpty($this->userApi->getClientRoles($user->id, $client->id));
    }

    public function testSendRequiredActionsEmail(): void
    {
        $user = $this->getUser();
        $this->expectExceptionMessageMatches('/Failed to send execute actions email/');
        $this->userApi->sendRequiredActionsEmail($user->id, ['UPDATE_PASSWORD']);
    }

    public function testSendVerifyEmail(): void
    {
        $user = $this->getUser();
        $this->expectExceptionMessageMatches('/Failed to send execute actions email/');
        $this->userApi->sendVerifyEmail($user->id);
    }

    public function testGetRequiredActions(): void
    {
        $requiredActions = $this->userApi->getRequiredActions();
        $this->assertIsArray($requiredActions);
    }

    public function testGetCredentials(): void
    {
        $user = $this->getUser();
        $credentials = $this->userApi->getCredentials($user->id);
        $this->assertCount(1, $credentials);
        $this->assertInstanceOf(Credential::class, array_pop($credentials));
    }

    public function testDeleteCredential(): void
    {
        $user = $this->getUser();
        $credentials = $this->userApi->getCredentials($user->id);
        $credential = array_pop($credentials);

        $this->userApi->deleteCredential($user->id, $credential->id);
        $credentials = $this->userApi->getCredentials($user->id);
        $this->assertCount(0, $credentials);
    }

    public function testDelete(): void
    {
        $user = $this->getUser();

        $this->userApi->delete($user->id);
        $deletedUser = $this->userApi->find($user->id);
        $this->assertNull($deletedUser);
    }
}
