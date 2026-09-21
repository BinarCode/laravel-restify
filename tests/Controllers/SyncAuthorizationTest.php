<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

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
        unset($_SERVER['allow_sync_users']);

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
}
