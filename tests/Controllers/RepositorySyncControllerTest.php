<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RepositorySyncControllerTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['roles.canDetach.users']);

        parent::tearDown();
    }

    public function test_can_sync_repositories(): void
    {
        $this->markTestSkipped('Doesnt run on ubuntu lowest');
        $user = $this->mockUsers()->first();
        $user1 = $this->mockUsers()->first();
        $user2 = $this->mockUsers()->first();

        /**
         * @var Company $company
         */
        $company = Company::factory()->create();

        $company->users()->attach($user1);
        $company->users()->attach($user2);

        $this->assertCount(2, $company->users()->get());

        $company->users()->first()->is($user1);

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => [$user->getKey()],
        ])->assertOk();

        $company->users()->first()->is($user);

        $this->assertCount(1, $company->users()->get());
    }

    #[Test]
    public function sync_is_denied_when_a_field_with_can_detach_would_remove_an_attached_row(): void
    {
        $_SERVER['roles.canDetach.users'] = false;

        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA, $userB): void {
            $company->users()->attach($userA->getKey(), ['is_admin' => true]);
            $company->users()->attach($userB->getKey(), ['is_admin' => false]);
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => [$userA->getKey()],
        ])->assertForbidden();

        $this->assertCount(2, $company->fresh()->users);
    }

    #[Test]
    public function sync_that_only_adds_rows_still_succeeds_when_the_field_has_can_detach(): void
    {
        $_SERVER['roles.canDetach.users'] = false;

        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA): void {
            $company->users()->attach($userA->getKey(), ['is_admin' => true]);
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => [$userA->getKey(), $userB->getKey()],
        ])->assertOk();

        $this->assertCount(2, $company->fresh()->users);
    }

    #[Test]
    public function sync_still_removes_rows_when_the_field_has_no_can_detach_callback(): void
    {
        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA, $userB): void {
            $company->staff()->attach($userA->getKey());
            $company->staff()->attach($userB->getKey());
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/staff"), [
            'staff' => [$userA->getKey()],
        ])->assertOk();

        $this->assertCount(1, $company->fresh()->staff);
    }
}
