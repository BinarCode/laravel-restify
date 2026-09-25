<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class SyncRemovalQueryCountTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function tearDown(): void
    {
        unset($_SERVER['roles.canDetach.users']);

        parent::tearDown();
    }

    #[Test]
    public function a_plain_field_sync_does_not_query_the_rows_it_removes(): void
    {
        $company = Company::factory()->state(['owner_id' => null])->create();
        $keptUser = User::factory()->create();
        $removedUser = User::factory()->create();
        $company->staff()->attach([$keptUser->getKey(), $removedUser->getKey()]);

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/staff"), [
            'staff' => [$keptUser->getKey()],
        ])->assertOk();

        $this->assertSame([], $this->removedPivotSelects());
    }

    #[Test]
    public function a_field_with_can_detach_queries_the_rows_it_removes(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $company = Company::factory()->state(['owner_id' => null])->create();
        $keptUser = User::factory()->create();
        $removedUser = User::factory()->create();
        $company->users()->attach([$keptUser->getKey(), $removedUser->getKey()], ['is_admin' => true]);

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$keptUser->getKey()],
        ])->assertOk();

        $this->assertCount(1, $this->removedPivotSelects());
    }

    /**
     * @return list<string>
     */
    private function removedPivotSelects(): array
    {
        return array_values(array_filter(
            $this->selectsAgainst(CompanyUserPivot::class),
            fn (string $query): bool => str_contains($query, '"user_id" not in'),
        ));
    }
}
