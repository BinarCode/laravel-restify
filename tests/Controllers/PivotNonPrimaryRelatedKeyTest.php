<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
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
}
