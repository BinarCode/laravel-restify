<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;

class AttachSyncParentLoadingTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private Company $company;

    /** @var Collection<int, User> */
    private Collection $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->users = User::factory(5)->create();

        $this->recordQueries();
    }

    #[Test]
    public function attaching_many_users_loads_the_company_once(): void
    {
        $this->postJson(CompanyRepository::route("{$this->company->id}/attach/users"), [
            'users' => $this->users->modelKeys(),
            'is_admin' => true,
        ])->assertCreated();

        $this->assertSelectCount(1, Company::class);
        $this->assertSelectCount(1, User::class);

        $this->assertDatabaseCount(CompanyUserPivot::class, 5);
    }

    #[Test]
    public function syncing_many_users_loads_the_company_once(): void
    {
        $this->postJson(CompanyRepository::route("{$this->company->id}/sync/users"), [
            'users' => $this->users->modelKeys(),
            'is_admin' => true,
        ])->assertOk();

        $this->assertSelectCount(1, Company::class);

        $this->assertDatabaseCount(CompanyUserPivot::class, 5);
    }
}
