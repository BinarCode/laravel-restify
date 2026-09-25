<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyLabelPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyWithExtraSyncedStaffRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\StringableKeyedLabelRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\StaffRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositorySyncControllerTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['roles.canDetach.users']);

        CompanyWithExtraSyncedStaffRepository::$extraStaffId = null;

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

    #[Test]
    public function sync_denies_a_genuine_removal_even_when_a_kept_id_is_given_as_a_non_canonical_numeric_string(): void
    {
        $_SERVER['roles.canDetach.users'] = false;

        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA, $userB): void {
            $company->users()->attach($userA->getKey(), ['is_admin' => true]);
            $company->users()->attach($userB->getKey(), ['is_admin' => false]);
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => ['0'.$userA->getKey()],
        ])->assertForbidden();

        $this->assertCount(2, $company->fresh()->users);
    }

    #[Test]
    public function sync_does_not_detach_a_kept_row_given_as_a_non_canonical_numeric_string_when_can_detach_allows(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA, $userB): void {
            $company->users()->attach($userA->getKey(), ['is_admin' => true]);
            $company->users()->attach($userB->getKey(), ['is_admin' => false]);
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => ['0'.$userA->getKey(), $userB->getKey()],
        ])->assertOk();

        $this->assertCount(2, $company->fresh()->users);
    }

    #[Test]
    public function sync_does_not_lose_a_kept_row_given_as_a_non_canonical_numeric_string_when_can_detach_denies(): void
    {
        $_SERVER['roles.canDetach.users'] = false;

        $userA = $this->mockUsers()->first();
        $userB = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($userA, $userB): void {
            $company->users()->attach($userA->getKey(), ['is_admin' => true]);
            $company->users()->attach($userB->getKey(), ['is_admin' => false]);
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => ['0'.$userA->getKey(), $userB->getKey()],
        ])->assertOk();

        $this->assertCount(2, $company->fresh()->users);
    }

    #[Test]
    public function sync_denies_a_genuine_removal_even_when_a_kept_id_is_given_in_a_different_case(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'badges' => BelongsToMany::make('badges', StringableKeyedLabelRepository::class)->canDetach(fn (): bool => false),
            ]);

        Label::query()->create(['code' => 'gold']);
        Label::query()->create(['code' => 'silver']);

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company): void {
            $company->badges()->attach('gold');
            $company->badges()->attach('silver');
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/badges"), [
            'badges' => ['GOLD'],
        ])->assertForbidden();

        $this->assertCount(2, $company->fresh()->badges);
    }

    #[Test]
    public function sync_does_not_detach_a_kept_row_given_in_a_different_case_when_can_detach_allows(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'badges' => BelongsToMany::make('badges', StringableKeyedLabelRepository::class)->canDetach(fn (): bool => true),
            ]);

        Label::query()->create(['code' => 'gold']);

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company): void {
            $company->badges()->attach('gold');
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/badges"), [
            'badges' => ['GOLD'],
        ])->assertOk();

        $this->assertCount(1, $company->fresh()->badges);

        $this->assertSame(
            'gold',
            CompanyLabelPivot::query()->where('company_id', $company->getKey())->value('label_code')
        );
    }

    #[Test]
    public function sync_does_not_detach_a_kept_row_given_in_a_different_case_when_can_detach_denies(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'badges' => BelongsToMany::make('badges', StringableKeyedLabelRepository::class)->canDetach(fn (): bool => false),
            ]);

        Label::query()->create(['code' => 'gold']);

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company): void {
            $company->badges()->attach('gold');
        });

        $this->postJson(CompanyRepository::route("$company->id/sync/badges"), [
            'badges' => ['GOLD'],
        ])->assertOk();

        $this->assertCount(1, $company->fresh()->badges);

        $this->assertSame(
            'gold',
            CompanyLabelPivot::query()->where('company_id', $company->getKey())->value('label_code')
        );
    }

    #[Test]
    #[TestWith([''], 'the canonical key')]
    #[TestWith(['0'], 'a zero-padded key')]
    public function sync_writes_the_ids_a_repository_override_passes_to_the_parent(string $keyPrefix): void
    {
        Restify::repositories([CompanyWithExtraSyncedStaffRepository::class]);

        $requestedUser = $this->mockUsers()->first();
        $extraUser = $this->mockUsers()->first();

        CompanyWithExtraSyncedStaffRepository::$extraStaffId = "{$keyPrefix}{$extraUser->getKey()}";

        $company = Company::factory()->state(['owner_id' => null])->create();

        $this->postJson(CompanyWithExtraSyncedStaffRepository::route("{$company->getKey()}/sync/staff"), [
            'staff' => [$requestedUser->getKey()],
        ])->assertOk();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $requestedUser->getKey(),
        ]);
        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $extraUser->getKey(),
        ]);
        $this->assertDatabaseCount(CompanyUserPivot::class, 2);
    }

    #[Test]
    public function sync_is_denied_when_a_field_subclass_denies_detaching_without_a_can_detach_callback(): void
    {
        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'staff' => new class('staff', StaffRepository::class) extends BelongsToMany
                {
                    public function authorizedToDetach(RestifyRequest $request, Pivot $pivot): bool
                    {
                        return false;
                    }
                },
            ]);

        $keptUser = $this->mockUsers()->first();
        $removedUser = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($keptUser, $removedUser): void {
            $company->staff()->attach([$keptUser->getKey(), $removedUser->getKey()]);
        });

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/staff"), [
            'staff' => [$keptUser->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $removedUser->getKey(),
        ]);
    }

    #[Test]
    public function a_sync_that_adds_and_removes_writes_nothing_when_the_removal_is_denied(): void
    {
        $_SERVER['roles.canDetach.users'] = false;

        $attachedUser = $this->mockUsers()->first();
        $addedUser = $this->mockUsers()->first();

        $company = tap(Company::factory()->state(['owner_id' => null])->create(), function (Company $company) use ($attachedUser): void {
            $company->users()->attach($attachedUser->getKey(), ['is_admin' => true]);
        });

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$addedUser->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $attachedUser->getKey(),
        ]);
        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $addedUser->getKey(),
        ]);
    }
}
