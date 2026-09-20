<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class AttachSyncParentLoadingTest extends IntegrationTestCase
{
    #[Test]
    public function attaching_many_users_loads_the_company_once(): void
    {
        $company = Company::factory()->create();
        $users = User::factory(5)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => $users->modelKeys(),
            'is_admin' => true,
        ])->assertCreated();

        $this->assertCount(1, $this->selectsAgainst(Company::class));
        $this->assertCount(1, $this->selectsAgainst(User::class));

        $this->assertDatabaseCount(CompanyUserPivot::class, 5);
        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $users->first()->getKey(),
        ]);
    }

    #[Test]
    public function syncing_many_users_loads_the_company_once(): void
    {
        $company = Company::factory()->create();
        $users = User::factory(5)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson(CompanyRepository::route("{$company->id}/sync/users"), [
            'users' => $users->modelKeys(),
            'is_admin' => true,
        ])->assertOk();

        $this->assertCount(1, $this->selectsAgainst(Company::class));

        $this->assertDatabaseCount(CompanyUserPivot::class, 5);
    }

    #[Test]
    public function attaching_accepts_a_related_id_the_database_matches(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => ['0'.$user->getKey()],
            'is_admin' => true,
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     * @return list<string>
     */
    private function selectsAgainst(string $model): array
    {
        $table = $this->getTable($model);

        return array_values(array_filter(
            array_column(DB::getQueryLog(), 'query'),
            fn (string $query): bool => str_starts_with($query, 'select ')
                && str_contains($query, 'from "'.$table.'"'),
        ));
    }
}
