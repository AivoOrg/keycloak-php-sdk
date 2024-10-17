<?php

declare(strict_types=1);

namespace App\Tests;

use Keycloak\Realm\Entity\NewAuthenticationConfig;
use Keycloak\Realm\Entity\NewAuthenticationExecution;
use Keycloak\Realm\Entity\NewAuthenticationFlow;
use Keycloak\Realm\Entity\Role;
use Keycloak\Realm\RealmApi;
use PHPUnit\Framework\TestCase;

final class RealmTest extends TestCase
{
    private RealmApi $realmApi;

    protected function setUp(): void
    {
        global $client;
        $this->realmApi = new RealmApi($client);
    }

    public function testGetAuthenticationFlows(): void
    {
        $authenticationFlows = $this->realmApi->getAuthenticationFlows();
        $this->assertGreaterThan(0, count($authenticationFlows));
    }

    public function testGetAuthenticationFlow(): void
    {
        $authenticationFlows = $this->realmApi->getAuthenticationFlows();

        $authenticationFlow = $this->realmApi->getAuthenticationFlow($authenticationFlows[0]->id);
        $this->assertNotNull($authenticationFlow);

        $authenticationFlow = $this->realmApi->getAuthenticationFlow('bogus-id');
        $this->assertNull($authenticationFlow);
    }

    public function testCreateAuthenticationFlow(): void
    {
        $oldAuthenticationFlows = $this->realmApi->getAuthenticationFlows();

        $flow = new NewAuthenticationFlow(
            'php-unit',
            'test flow',
            'basic-flow',
            true,
            []
        );
        $result = $this->realmApi->createAuthenticationFlow($flow);
        $this->assertNotEmpty($result);

        $newAuthenticationFlows = $this->realmApi->getAuthenticationFlows();
        $this->assertGreaterThan($oldAuthenticationFlows, $newAuthenticationFlows);
    }

    public function testGetAuthenticationFlowByAlias(): void
    {
        $authenticationFlow = $this->realmApi->getAuthenticationFlowByAlias('php-unit');
        $this->assertNotNull($authenticationFlow);
    }

    public function testAuthenticationFlowExecution(): void
    {
        $execution = new NewAuthenticationExecution('auth-conditional-otp-form');
        $executionId = $this->realmApi->createAuthenticationFlowExecution('php-unit', $execution);
        $this->assertNotEmpty($executionId);

        $execution = $this->realmApi->getAuthenticationFlowExecution('php-unit', $executionId);
        $execution->requirement = 'REQUIRED';

        $flow = $this->realmApi->updateAuthenticationFlowExecution('php-unit', $execution);
        $this->assertNotNull($flow);

        $execution = $this->realmApi->getAuthenticationFlowExecution('php-unit', $executionId);
        $this->assertEquals('REQUIRED', $execution->requirement);

        $config = new NewAuthenticationConfig('php-unit-auth-config', [
            'defaultOtpOutcome' => 'skip',
            'forceOtpRole' => 'realm-management.realm-admin',
            'forceOtpForHeaderPattern' => '',
            'noOtpRequiredForHeaderPattern' => ''
        ]);
        $configId = $this->realmApi->createAuthenticationConfig($executionId, $config);
        $config = $this->realmApi->getAuthenticationConfig($configId);
        $this->assertNotNull($config);

        $executions = $this->realmApi->getAuthenticationFlowExecutions('php-unit');
        $this->assertNotEmpty($executions);

        $this->realmApi->deleteAuthenticationFlowExecution($executionId);
        $this->realmApi->deleteAuthenticationConfig($configId);

        $executions = $this->realmApi->getAuthenticationFlowExecutions('php-unit');
        $this->assertEmpty($executions);
    }

    public function testDeleteAuthenticationFlow(): void
    {
        $oldAuthenticationFlows = $this->realmApi->getAuthenticationFlows();

        $authenticationFlowId = null;
        foreach ($oldAuthenticationFlows as $flow) {
            if ($flow->alias === 'php-unit') {
                $authenticationFlowId = $flow->id;
            }
        }
        $this->realmApi->deleteAuthenticationFlow($authenticationFlowId);

        $newAuthenticationFlows = $this->realmApi->getAuthenticationFlows();

        $authenticationFlowId = null;
        foreach ($newAuthenticationFlows as $flow) {
            if ($flow->alias === 'php-unit') {
                $authenticationFlowId = $flow->id;
            }
        }
        $this->assertNull($authenticationFlowId);
    }

    public function testGetRoles(): void
    {
        $roles = $this->realmApi->getRoles();
        $this->assertCount(3, $roles);
        foreach ($roles as $role) {
            $this->assertEquals(Role::class, get_class($role));
        }
    }
}
