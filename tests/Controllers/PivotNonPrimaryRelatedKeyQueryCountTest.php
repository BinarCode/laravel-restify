<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
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

        $this->assertSelectCount(2, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 3);
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

        $this->assertSelectCount(2, Role::class);

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

        $this->assertSelectCount(2, Role::class);

        $this->assertDatabaseCount(CompanyRolePivot::class, 0);
    }
}
