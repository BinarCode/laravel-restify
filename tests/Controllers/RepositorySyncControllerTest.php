<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositorySyncControllerTest extends IntegrationTest
{
    public function test_can_sync_repositories(): void
    {
        $user = $this->mockUsers()->first();
        $user1 = $this->mockUsers()->first();
        $user2 = $this->mockUsers()->first();

        /**
         * @var Company $company
         */
        $company = Company::factory()->create();

        $company->users()->attach($user1);
        $company->users()->attach($user2);

        $this->assertCount(2, Company::first()->users()->get());

        $company->users()->first()->is($user1);

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => $user->getKey(),
        ])->assertOk();

        $company->users()->first()->is($user);

        $this->assertCount(1, $company->users()->get());
    }
}
