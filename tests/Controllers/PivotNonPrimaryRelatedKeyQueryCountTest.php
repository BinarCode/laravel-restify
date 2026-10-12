<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyLabelPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyWithExtraSyncedStaffRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;

class PivotNonPrimaryRelatedKeyQueryCountTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private Company $company;

    /** @var Collection<int, Role> */
    private Collection $roles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->roles = Role::factory()->count(3)->sequence(
            ['name' => 'role-0'],
            ['name' => 'role-1'],
            ['name' => 'role-2'],
        )->create();

        $this->recordQueries();
    }

    #[Test]
    public function attaching_several_roles_batches_the_related_model_lookup(): void
    {
        $this->postJson(CompanyRepository::route("{$this->company->id}/attach/roles"), [
            'roles' => $this->roles->modelKeys(),
        ])->assertCreated();

        $this->assertSelectCount(1, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 3);
    }

    #[Test]
    public function attaching_several_roles_by_zero_padded_ids_resolves_them_in_one_lookup(): void
    {
        $this->postJson(CompanyRepository::route("{$this->company->id}/attach/roles"), [
            'roles' => $this->roles->map(fn (Role $role): string => '0'.$role->getKey())->all(),
        ])->assertCreated();

        $this->assertSelectCount(1, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 3);
    }

    #[Test]
    public function attaching_several_labels_by_differently_cased_ids_retries_them_in_one_batch(): void
    {
        foreach (['aaa', 'bbb', 'ccc'] as $code) {
            Label::query()->create(['code' => $code]);
        }

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$this->company->id}/attach/labels"), [
            'labels' => ['AAA', 'BBB', 'CCC'],
        ])->assertCreated();

        $this->assertSelectCount(2, Label::class);

        $this->assertDatabaseCount(CompanyLabelPivot::class, 3);
    }

    #[Test]
    public function syncing_ids_a_repository_override_adds_resolves_them_in_one_lookup(): void
    {
        Restify::repositories([CompanyWithExtraSyncedStaffRepository::class]);

        $requestedUser = User::factory()->create();
        $extraUsers = User::factory()->count(2)->create();

        CompanyWithExtraSyncedStaffRepository::$extraStaffId = $extraUsers->first()->getKey();

        $this->recordQueries();

        $this->postJson(CompanyWithExtraSyncedStaffRepository::route("{$this->company->id}/sync/staff"), [
            'staff' => [$requestedUser->getKey()],
        ])->assertOk();

        $this->assertSelectCount(2, User::class);

        $this->assertDatabaseCount(CompanyUserPivot::class, 2);
    }

    #[Test]
    public function attaching_several_roles_invokes_can_attach_exactly_once_per_id(): void
    {
        $calls = 0;

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'roles' => BelongsToMany::make('roles', RoleRepository::class)
                    ->canAttach(function () use (&$calls) {
                        $calls++;

                        return true;
                    }),
            ]);

        $this->postJson(CompanyRepository::route("{$this->company->id}/attach/roles"), [
            'roles' => $this->roles->modelKeys(),
        ])->assertCreated();

        $this->assertSame(3, $calls);
    }

    #[Test]
    public function syncing_several_roles_batches_the_related_model_lookup(): void
    {
        $this->postJson(CompanyRepository::route("{$this->company->id}/sync/roles"), [
            'roles' => $this->roles->modelKeys(),
        ])->assertOk();

        $this->assertSelectCount(1, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 3);
    }

    #[Test]
    public function detaching_several_roles_batches_the_related_model_lookup(): void
    {
        $this->company->roles()->attach(
            $this->roles->pluck('name')->all()
        );

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$this->company->id}/detach/roles"), [
            'roles' => $this->roles->modelKeys(),
        ])->assertNoContent();

        $this->assertSelectCount(1, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 0);
    }
}
