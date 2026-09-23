<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class PivotNonPrimaryRelatedKeyTest extends IntegrationTestCase
{
    #[Test]
    public function it_attaches_a_relation_whose_related_key_is_not_the_related_models_primary_key(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->create(['name' => 'engineering']);

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => $role->getKey(),
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $role->name,
        ]);
    }

    #[Test]
    public function it_detaches_a_relation_whose_related_key_is_not_the_related_models_primary_key(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->create(['name' => 'engineering']);

        $company->roles()->attach($role->name);

        $this->assertDatabaseHas(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $role->name,
        ]);

        $this->postJson(CompanyRepository::route("{$company->id}/detach/roles"), [
            'roles' => [$role->getKey()],
        ])->assertNoContent();

        $this->assertDatabaseMissing(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $role->name,
        ]);
    }

    #[Test]
    public function it_syncs_a_relation_whose_related_key_is_not_the_related_models_primary_key(): void
    {
        $company = Company::factory()->create();
        $stale = Role::factory()->create(['name' => 'stale']);
        $role = Role::factory()->create(['name' => 'engineering']);

        $company->roles()->attach($stale->name);

        $this->postJson(CompanyRepository::route("{$company->id}/sync/roles"), [
            'roles' => [$role->getKey()],
        ])->assertOk();

        $this->assertDatabaseHas(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $role->name,
        ]);

        $this->assertDatabaseMissing(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $stale->name,
        ]);
    }

    #[Test]
    public function it_resolves_the_parent_by_its_actual_key_when_the_route_key_differs_from_the_primary_key(): void
    {
        Restify::repositories([CompanyBySlugRepository::class]);

        $company = Company::factory()->create(['name' => 'acme-corp']);
        $role = Role::factory()->create(['name' => 'engineering']);

        $this->postJson(CompanyBySlugRepository::route('acme-corp/attach/roles'), [
            'roles' => $role->getKey(),
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => $role->name,
        ]);
    }

    #[Test]
    public function attaching_a_nested_array_id_returns_a_validation_error(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => [['id' => 1]],
        ])->assertStatus(422);
    }

    #[Test]
    public function attaching_a_null_id_returns_a_validation_error(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => [null],
        ])->assertStatus(422);
    }

    #[Test]
    public function attaching_an_unknown_id_on_the_primary_key_relation_returns_not_found(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [999999],
        ])->assertNotFound();
    }

    #[Test]
    public function attaching_an_unknown_id_on_the_non_primary_key_relation_returns_not_found(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => [999999],
        ])->assertNotFound();
    }

    #[Test]
    public function detaching_an_unknown_id_on_the_primary_key_relation_returns_not_found(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/detach/users"), [
            'users' => [999999],
        ])->assertNotFound();
    }

    #[Test]
    public function detaching_an_unknown_id_on_the_non_primary_key_relation_returns_not_found(): void
    {
        $company = Company::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/detach/roles"), [
            'roles' => [999999],
        ])->assertNotFound();
    }

    #[Test]
    public function can_attach_intercepts_authorization_for_the_non_primary_key_relation(): void
    {
        $role = Role::factory()->create(['name' => 'engineering']);
        $company = Company::factory()->create();

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'roles' => BelongsToMany::make('roles', RoleRepository::class)
                    ->canAttach(fn ($request, $pivot) => false),
            ]);

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => $role->getKey(),
        ])->assertForbidden();
    }
}

class CompanyBySlug extends Company
{
    protected $table = 'companies';

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}

class CompanyBySlugRepository extends CompanyRepository
{
    public static $model = CompanyBySlug::class;

    public static $uriKey = 'companies-by-slug';
}
