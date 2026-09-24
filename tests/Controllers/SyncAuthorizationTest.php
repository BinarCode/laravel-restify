<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositorySyncRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class SyncAuthorizationTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['allow_sync_users'],
            $_SERVER['companies.canSync.users'],
            $_SERVER['companies.canSync.denied_user_ids'],
            $_SERVER['companies.canSync.calls'],
        );

        parent::tearDown();
    }

    #[Test]
    public function a_denied_sync_policy_writes_nothing(): void
    {
        $_SERVER['allow_sync_users'] = false;

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function an_allowed_sync_policy_writes_the_pivot(): void
    {
        $_SERVER['allow_sync_users'] = true;

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])->assertOk();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    public function a_denied_can_sync_callback_writes_nothing(): void
    {
        $_SERVER['allow_sync_users'] = true;
        $_SERVER['companies.canSync.users'] = false;

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function a_can_sync_callback_denying_one_id_leaves_the_whole_sync_unapplied(): void
    {
        $_SERVER['allow_sync_users'] = true;

        $company = Company::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$userA->getKey()],
        ])->assertOk();

        $_SERVER['companies.canSync.denied_user_ids'] = [$userC->getKey()];

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$userB->getKey(), $userC->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $userA->getKey(),
        ]);

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $userB->getKey(),
        ]);
    }

    #[Test]
    public function the_can_sync_callback_runs_once_per_id_with_a_repository_sync_request(): void
    {
        $_SERVER['allow_sync_users'] = true;
        $_SERVER['companies.canSync.calls'] = [];

        $company = Company::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [(string) $userB->getKey(), (string) $userC->getKey()],
        ])->assertOk();

        $calls = $_SERVER['companies.canSync.calls'];

        $this->assertCount(2, $calls);

        foreach ($calls as $call) {
            $this->assertSame(RepositorySyncRequest::class, $call[0]);
            $this->assertEquals($company->getKey(), $call[1]);
        }

        $this->assertEqualsCanonicalizing(
            [$userB->getKey(), $userC->getKey()],
            array_column($calls, 2),
        );
    }
}
