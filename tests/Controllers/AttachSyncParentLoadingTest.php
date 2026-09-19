<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
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

        $this->assertCount(1, $this->selectsAgainst('companies'));
        $this->assertCount(1, $this->selectsAgainst('users'));
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

        $this->assertCount(1, $this->selectsAgainst('companies'));
    }

    /**
     * @return list<string>
     */
    private function selectsAgainst(string $table): array
    {
        $executed = array_column(DB::getQueryLog(), 'query');

        return array_values(array_filter(
            $executed,
            fn (string $query): bool => str_starts_with($query, 'select ')
                && str_contains($query, 'from "'.$table.'"'),
        ));
    }
}
