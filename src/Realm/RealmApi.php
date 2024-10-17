<?php

declare(strict_types=1);

namespace Keycloak\Realm;

use Keycloak\Exception\KeycloakException;
use Keycloak\KeycloakClient;
use Keycloak\Realm\Entity\AuthenticationConfig;
use Keycloak\Realm\Entity\AuthenticationExecution;
use Keycloak\Realm\Entity\AuthenticationFlow;
use Keycloak\Realm\Entity\NewAuthenticationConfig;
use Keycloak\Realm\Entity\NewAuthenticationExecution;
use Keycloak\Realm\Entity\NewAuthenticationFlow;
use Keycloak\Service\CreateResponseService;
use Keycloak\Realm\Entity\Role;

class RealmApi
{
    private KeycloakClient $client;

    public function __construct(KeycloakClient $client)
    {
        $this->client = $client;
    }

    public function find(): ?array
    {
        try {
            $json = $this->client->sendRequest('GET', '')->getBody()->getContents();
            return json_decode($json, true);
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null || $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    public function createAuthenticationFlow(NewAuthenticationFlow $flow): string
    {
        $res = $this->client->sendRequest('POST', 'authentication/flows', $flow);
        return CreateResponseService::handleCreateResponse($res);
    }

    /**
     * @return array|AuthenticationFlow[]
     */
    public function getAuthenticationFlows(): array
    {
        $authenticationFlows = $this->client
            ->sendRequest('GET', 'authentication/flows')
            ->getBody()
            ->getContents();

        return array_map(static function (array $authenticationFlow): AuthenticationFlow {
            return AuthenticationFlow::fromJson($authenticationFlow);
        }, json_decode($authenticationFlows, true));
    }

    public function getAuthenticationFlow(string $id): ?AuthenticationFlow
    {
        try {
            return AuthenticationFlow::fromJson($this->client
                ->sendRequest('GET', "authentication/flows/$id")
                ->getBody()
                ->getContents());
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null && $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    public function getAuthenticationFlowByAlias(string $alias): ?AuthenticationFlow
    {
        $authenticationFlows = $this->getAuthenticationFlows();
        foreach ($authenticationFlows as $authenticationFlow) {
            if ($authenticationFlow->alias === $alias) {
                return $authenticationFlow;
            }
        }
        return null;
    }

    public function deleteAuthenticationFlow(string $id): void
    {
        $this->client->sendRequest('DELETE', "authentication/flows/$id");
    }

    public function createAuthenticationFlowExecution(string $flowAlias, NewAuthenticationExecution $execution): string
    {
        $res = $this->client->sendRequest('POST', "authentication/flows/$flowAlias/executions/execution", $execution);
        return CreateResponseService::handleCreateResponse($res);
    }

    public function getAuthenticationFlowExecution(string $flowAlias, string $id): ?AuthenticationExecution
    {
        $executions = $this->getAuthenticationFlowExecutions($flowAlias);
        foreach ($executions as $execution) {
            if ($execution->id === $id) {
                return $execution;
            }
        }
        return null;
    }

    /**
     * @param string $flowAlias
     * @return array|AuthenticationExecution[]
     */
    public function getAuthenticationFlowExecutions(string $flowAlias): array
    {
        $executions = $this->client
            ->sendRequest('GET', "authentication/flows/$flowAlias/executions")
            ->getBody()
            ->getContents();

        return array_map(static function (array $execution): AuthenticationExecution {
            return AuthenticationExecution::fromJson($execution);
        }, json_decode($executions, true));
    }

    public function updateAuthenticationFlowExecution(string $flowAlias, AuthenticationExecution $execution): ?AuthenticationFlow
    {
        try {
            return AuthenticationFlow::fromJson(
                $this->client
                    ->sendRequest('PUT', "authentication/flows/$flowAlias/executions", $execution)
                    ->getBody()
                    ->getContents());
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null && $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    public function deleteAuthenticationFlowExecution(string $executionId): void
    {
        $this->client->sendRequest('DELETE', "authentication/executions/$executionId");
    }

    public function getAuthenticationConfig(string $id): ?AuthenticationConfig
    {
        try {
            return AuthenticationConfig::fromJson($this->client
                ->sendRequest('GET', "authentication/config/$id")
                ->getBody()
                ->getContents());
        } catch (KeycloakException $ex) {
            if ($ex->getPrevious() === null && $ex->getPrevious()->getCode() !== 404) {
                throw $ex;
            }
        }
        return null;
    }

    public function createAuthenticationConfig(string $executionId, NewAuthenticationConfig $config): string
    {
        $res = $this->client->sendRequest('POST', "authentication/executions/$executionId/config", $config);
        return CreateResponseService::handleCreateResponse($res);
    }

    public function deleteAuthenticationConfig(string $configId): void
    {
        $this->client->sendRequest('DELETE', "authentication/config/$configId");
    }

    public function getRoles(): array
    {
        $json = $this->client->sendRequest('GET', 'roles')
            ->getBody()
            ->getContents();

        $jsonDecoded = json_decode($json, true);
        if ($jsonDecoded === null) {
            return [];
        }

        return array_map(static function ($roleArr): Role {
            return Role::fromJson($roleArr);
        }, $jsonDecoded);
    }
}
