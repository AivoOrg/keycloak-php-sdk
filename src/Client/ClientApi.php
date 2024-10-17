<?php

namespace Keycloak\Client;

use JsonException;
use Keycloak\Client\Entity\Client;
use Keycloak\Exception\KeycloakException;
use Keycloak\KeycloakClient;
use Keycloak\Service\CreateResponseService;
use Keycloak\User\Entity\CompositeRole;
use Keycloak\Client\Entity\Role;
use Keycloak\User\Entity\User;

class ClientApi
{
    private KeycloakClient $client;

    public function __construct(KeycloakClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $id
     * @return Client|null
     * @throws KeycloakException
     */
    public function find(string $id): ?Client
    {
        try {
            return Client::fromJson($this->client
                ->sendRequest('GET', "clients/$id")
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
     * @return Client[]
     * @throws KeycloakException
     */
    public function findAll(): array
    {
        $json = $this->client
            ->sendRequest('GET', 'clients')
            ->getBody()
            ->getContents();
        return array_map(static function ($clientArr): Client {
            return Client::fromJson($clientArr);
        }, json_decode($json, true));
    }

    /**
     * @param string $clientId
     * @return Client|null
     * @throws KeycloakException
     */
    public function findByClientId(string $clientId): ?Client
    {
        $allClients = $this->findAll();
        foreach ($allClients as $client) {
            if ($client->clientId === $clientId) {
                return $client;
            }
        }
        return null;
    }

    public function create(string $clientId): string
    {
        $res = $this->client->sendRequest('POST', 'clients', ['clientId' => $clientId]);
        return CreateResponseService::handleCreateResponse($res);
    }

    public function update(Client $client): void
    {
        $this->client->sendRequest('PUT', "clients/{$client->id}", $client);
    }

    public function delete(string $id): void
    {
        $this->client->sendRequest('DELETE', "clients/$id");
    }

    public function getServiceAccountUser(string $id): ?User
    {
        try {
            return User::fromJson($this->client
                ->sendRequest('GET', "clients/$id/service-account-user")
                ->getBody()
                ->getContents());
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null || $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    public function getClientSecret(string $id): ?string
    {
        return $this->clientSecret('GET', $id);
    }

    public function generateClientSecret(string $id): string
    {
        return $this->clientSecret('POST', $id);
    }

    private function clientSecret(string $method, string $id): ?string
    {
        try {
            $data = $this->client
                ->sendRequest($method, "clients/{$id}/client-secret")
                ->getBody()
                ->getContents();

            return json_decode($data, true, 512, JSON_THROW_ON_ERROR)['value'] ?? null;
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null || $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        } catch (JsonException $ex) {
            throw new KeycloakException('Unable to decode client secret', $ex->getCode(), $ex);
        }
        return null;
    }

    /**
     * @param Role $role
     * @param string $clientId
     * @return Role
     * @throws KeycloakException
     */
    public function getRole(Role $role, string $clientId): ?Role
    {
        try {
            return Role::fromJson($this->client
                ->sendRequest('GET', "clients/$clientId/roles/{$role->name}")
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
     * @param string $clientId
     * @param string $roleName
     * @param int|null $first
     * @param int|null $max
     * @return User[]
     * @throws KeycloakException
     */
    public function findUsersByRoleName(string $clientId, string $roleName, ?int $first, ?int $max): array
    {
        $client = $this->findByClientId($clientId);
        if ($client === null) {
            return [];
        }

        $query = [];
        if ($first !== null) {
            $query['first'] = $first;
        }
        if ($max !== null) {
            $query['max'] = $max;
        }

        $queryString = empty($query) ? '' : '?' . http_build_query($query);

        $users = $this->client
            ->sendRequest('GET', "clients/{$client->id}/roles/{$roleName}/users{$queryString}")
            ->getBody()
            ->getContents();

        return array_map(static function ($userArr): User {
            return User::fromJson($userArr);
        }, json_decode($users, true));
    }

    /**
     * @param string $id
     * @return Role[]
     * @throws KeycloakException
     */
    public function getRoles(string $id): array
    {
        $json = $this->client
            ->sendRequest('GET', "clients/$id/roles")
            ->getBody()
            ->getContents();

        return array_map(static function ($roleArr) use ($id): Role {
            $roleArr['clientId'] = $id;
            return Role::fromJson($roleArr);
        }, json_decode($json, true));
    }

    /**
     * @param Role $role
     * @param string $clientId
     * @return string id of newly created role
     * @throws KeycloakException|JsonException
     */
    public function createRole(Role $role, string $clientId): string
    {
        $res = $this->client->sendRequest('POST', "clients/$clientId/roles", $role);
        return CreateResponseService::handleCreateResponse($res);
    }

    public function addPermissionsByRoleId(string $roleId, array $permissions): void
    {
        $this->client->sendRequest('POST', "roles-by-id/$roleId/composites", $permissions);
    }

    public function deletePermissionsByRoleId(string $roleId, array $permissions): void
    {
        $this->client->sendRequest('DELETE', "roles-by-id/$roleId/composites", $permissions);
    }

    /**
     * @param Role $role
     * @param string $clientId
     */
    public function updateRole(Role $role, string $clientId): void
    {
        $this->client->sendRequest('PUT', "clients/$clientId/roles/$role->name;", $role);
    }

    /**
     * @param string $roleName
     * @param string $clientId
     * @param array $permissions
     * @throws KeycloakException
     */
    public function addPermissions(string $roleName, string $clientId, ?array $permissions): void
    {
        $this->client->sendRequest('POST', "clients/$clientId/roles/$roleName/composites", $permissions);
    }

    /**
     * @param string $clientId
     * @param array $permissions
     */
    public function deletePermissions(string $clientId, array $permissions): void
    {
        $this->client->sendRequest('DELETE', "roles-by-id/$clientId/composites", array_values($permissions));
    }

    /**
     * @param string $clientId
     * @param string $roleName
     * @throws KeycloakException
     */
    public function deleteRole(string $roleName, string $clientId): void
    {
        $this->client->sendRequest('DELETE', "clients/$clientId/roles/" . $roleName);
    }

    /**
     * @param string $id
     * @return array
     */
    public function getCompositeRoles(string $id): array
    {
        $json = $this->client
            ->sendRequest('GET', "clients/$id/roles")
            ->getBody()
            ->getContents();

        $jsonDecoded = json_decode($json, true);
        if ($jsonDecoded === null) {
            return [];
        }

        $filtered = array_values(array_filter($jsonDecoded, static function ($roleArr): bool {
            return $roleArr['composite'];
        }));

        return array_map(static function ($roleArr) use ($id): Role {
            $roleArr['clientId'] = $id;
            return Role::fromJson($roleArr);
        }, $filtered);
    }

    /**
     * @param string $id
     * @return array
     */
    public function getCompositeRolesWithPermissions(string $id): array
    {
        $json = $this->client
            ->sendRequest('GET', "clients/$id/roles")
            ->getBody()
            ->getContents();

        $jsonDecoded = json_decode($json, true);
        if ($jsonDecoded === null) {
            return [];
        }

        $filtered = array_values(array_filter($jsonDecoded, static function ($roleArr): bool {
            return $roleArr['composite'];
        }));

        return array_map(function ($roleArr) use ($id): CompositeRole {
            $roleArr['clientId'] = $id;
            $roleArr['permissions'] = $this->getCompositesFromRole($id, $roleArr['name']);
            return CompositeRole::fromJson($roleArr);
        }, $filtered);
    }

    /**
     * @param string $id
     * @param string $roleName
     * @return array
     */
    public function getCompositesFromRole(string $id, string $roleName): array
    {
        $json = $this->client
            ->sendRequest('GET', "clients/$id/roles/$roleName/composites")
            ->getBody()
            ->getContents();

        $jsonDecoded = json_decode($json, true);
        if ($jsonDecoded === null) {
            return [];
        }

        return array_map(static function ($roleArr) use ($id): Role {
            $roleArr['clientId'] = $id;
            return Role::fromJson($roleArr);
        }, $jsonDecoded);
    }
}
