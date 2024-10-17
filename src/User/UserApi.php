<?php
namespace Keycloak\User;

use JsonException;
use Keycloak\Exception\KeycloakException;
use Keycloak\KeycloakClient;
use Keycloak\Service\CreateResponseService;
use Keycloak\User\Entity\Credential;
use Keycloak\User\Entity\NewUser;
use Keycloak\User\Entity\Role;
use Keycloak\User\Entity\Transformer\RoleTransformer;
use Keycloak\User\Entity\User;

class UserApi
{
    /**
     * @var KeycloakClient
     */
    private $client;

    /**
     * Api constructor.
     * @param KeycloakClient $client
     */
    public function __construct(KeycloakClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $id
     * @return User
     * @throws KeycloakException
     */
    public function find(string $id): ?User
    {
        try {
            return User::fromJson($this->client
                ->sendRequest('GET', "users/$id")
                ->getBody()
                ->getContents());
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null || $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    /**
     * @param array $query Can be used for more specific list searches.
     * @Link https://www.keycloak.org/docs-api/23.0.1/rest-api/index.html#_users
     * @return array<User>
     * @throws KeycloakException
     * @throws JsonException
     */
    public function findAll(array $query = []): array
    {
        $query['first'] = $query['first'] ?? 0;
        $query['max'] = $query['max'] ?? $this->count();
        $params = http_build_query($query);
        $json = $this->client
            ->sendRequest('GET', 'users' . ($params ? "?$params" : ''))
            ->getBody()
            ->getContents();
        return array_map(static function ($userArr): User {
            return User::fromJson($userArr);
        }, json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws KeycloakException
     */
    public function count(array $query = []): int
    {
        $params = http_build_query($query);
        return (int)$this->client
            ->sendRequest('GET', 'users/count' . ($params ? "?$params" : ''))
            ->getBody()
            ->getContents();
    }

    /**
     * @param NewUser $newUser
     * @return string id of the newly created user
     * @throws KeycloakException
     */
    public function create(NewUser $newUser): string
    {
        $res = $this->client->sendRequest('POST', 'users', $newUser);
        return CreateResponseService::handleCreateResponse($res);
    }

    /**
     * @param User $user
     * @throws KeycloakException
     */
    public function update(User $user): void
    {
        $this->client->sendRequest('PUT', "users/{$user->id}", $user);
    }

    /**
     * @param string $id
     * @throws KeycloakException
     */
    public function delete(string $id): void
    {
        $this->client->sendRequest('DELETE', "users/$id");
    }

    /**
     * @param string $id
     * @param string $newPassword
     * @param bool $temporary
     * @throws KeycloakException
     */
    public function resetPassword(string $id, string $newPassword, bool $temporary = false): void
    {
        $passwordReset = [
            'type' => 'password',
            'value' => $newPassword,
            'temporary' => $temporary
        ];
        $this->client->sendRequest('PUT', "users/$id/reset-password", $passwordReset);
    }

    /**
     * @param string $id
     * @return Role[]
     * @throws KeycloakException|JsonException
     */
    public function getRoles(string $id): array
    {
        $roleJson = $this->client
            ->sendRequest('GET', "users/$id/role-mappings")
            ->getBody()
            ->getContents();
        $roleArr = json_decode($roleJson, true, 512, JSON_THROW_ON_ERROR);

        $realmRoles = !empty($roleArr['realmMappings'])
            ? array_map(RoleTransformer::createRoleTransformer(null), $roleArr['realmMappings'])
            : [];

        $clientRoles = !empty($roleArr['clientMappings'])
            ? array_reduce($roleArr['clientMappings'], [RoleTransformer::class, 'transformClientRoles'], [])
            : [];
        return array_merge($realmRoles, $clientRoles);
    }

    /**
     * @param string $id
     * @return Role[]
     * @throws JsonException
     */
    public function getRealmRoles(string $id): array
    {
        $realmRolesJson = $this->client
            ->sendRequest('GET', "users/$id/role-mappings/realm")
            ->getBody()
            ->getContents();

        $realmRolesArr = json_decode($realmRolesJson, true, 512, JSON_THROW_ON_ERROR);
        return array_map(RoleTransformer::createRoleTransformer(null), $realmRolesArr);
    }

    /**
     * @param string $id
     * @return Role[]
     * @throws JsonException
     */
    public function getAvailableRealmRoles(string $id): array
    {
        $realmRolesJson = $this->client
            ->sendRequest('GET', "users/$id/role-mappings/realm/available")
            ->getBody()
            ->getContents();

        $realmRolesArr = json_decode($realmRolesJson, true, 512, JSON_THROW_ON_ERROR);
        return array_map(RoleTransformer::createRoleTransformer(null), $realmRolesArr);
    }

    public function addRealmRoles(string $id, array $rolesToAdd): void
    {
        $this->client->sendRequest(
            'POST',
            "users/$id/role-mappings/realm",
            array_map([RoleTransformer::class, 'toMinimalIdentifiableRole'], $rolesToAdd)
        );
    }

    public function deleteRealmRoles(string $id, array $rolesToDelete): void
    {
        $this->client->sendRequest(
            'DELETE',
            "users/$id/role-mappings/realm",
            array_map([RoleTransformer::class, 'toMinimalIdentifiableRole'], $rolesToDelete)
        );
    }

    /**
     * @param string $id
     * @param string $clientId
     * @return Role[]
     * @throws KeycloakException
     * @throws JsonException
     */
    public function getClientRoles(string $id, string $clientId): array
    {
        $clientRolesJson = $this->client
            ->sendRequest('GET', "users/$id/role-mappings/clients/$clientId")
            ->getBody()
            ->getContents();

        $clientRolesArr = json_decode($clientRolesJson, true, 512, JSON_THROW_ON_ERROR);
        return array_map(RoleTransformer::createRoleTransformer($clientId), $clientRolesArr);
    }

    /**
     * @param string $id
     * @param string $clientId
     * @return Role[]
     * @throws KeycloakException
     * @throws JsonException
     */
    public function getAvailableClientRoles(string $id, string $clientId): array
    {
        $clientRolesJson = $this->client
            ->sendRequest('GET', "users/$id/role-mappings/clients/$clientId/available")
            ->getBody()
            ->getContents();

        $clientRolesArr = json_decode($clientRolesJson, true, 512, JSON_THROW_ON_ERROR);
        return array_map(RoleTransformer::createRoleTransformer($clientId), $clientRolesArr);
    }

    /**
     * @param string $id
     * @param string $clientId
     * @param Role[] $rolesToAdd
     */
    public function addClientRoles(string $id, string $clientId, array $rolesToAdd): void
    {
        $this->client
            ->sendRequest(
                'POST',
                "users/$id/role-mappings/clients/$clientId",
                array_map([RoleTransformer::class, 'toMinimalIdentifiableRole'], $rolesToAdd));
    }

    /**
     * @param string $id
     * @param string $clientId
     * @param array $roleToAdd
     */
    public function addClientRolesWithMinimalInfo(string $id, string $clientId, array $roleToAdd): void
    {
        $this->client
            ->sendRequest(
                'POST',
                "users/$id/role-mappings/clients/$clientId",
                [$roleToAdd]);
    }

    /**
     * @param string $id
     * @param string $clientId
     * @param Role[] $rolesToDelete
     * @throws KeycloakException
     */
    public function deleteClientRoles(string $id, string $clientId, array $rolesToDelete): void
    {
        $this->client
            ->sendRequest(
                'DELETE',
                "users/$id/role-mappings/clients/$clientId",
                array_map([RoleTransformer::class, 'toMinimalIdentifiableRole'], $rolesToDelete));
    }

    /**
     * @param string $id
     * @param array $body
     */
    public function sendRequiredActionsEmail(string $id, array $body, string $query = ''): void
    {
        $this->client
            ->sendRequest(
                'PUT',
                "users/$id/execute-actions-email$query",
                $body
            );
    }

    /**
     * @param string $id
     */
    public function sendVerifyEmail(string $id): void
    {
        $this->client
            ->sendRequest(
                'PUT',
                "users/$id/send-verify-email"
            );
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function getRequiredActions(): array
    {
        $requiredActionsJson = $this->client
            ->sendRequest('GET', 'authentication/required-actions')
            ->getBody()
            ->getContents();

        $requiredActionsArr = json_decode($requiredActionsJson, true, 512, JSON_THROW_ON_ERROR);
        return array_map(static function ($action) {
            return $action['alias'];
        }, $requiredActionsArr);
    }


    /**
     * @param string $id
     * @return array
     * @throws JsonException
     */
    public function getCredentials(string $id): array
    {
        $credentialsJson = $this->client
            ->sendRequest('GET', "users/$id/credentials")
            ->getBody()
            ->getContents();

        return array_map(static function ($credentialsArr): Credential {
            return Credential::fromJson($credentialsArr);
        }, json_decode($credentialsJson, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @param string $id
     * @param string $credentialId
     * @return void
     */
    public function deleteCredential(string $id, string $credentialId): void
    {
        $this->client->sendRequest('DELETE', "users/$id/credentials/$credentialId");
    }
}
