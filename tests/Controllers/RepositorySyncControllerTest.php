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

        $company = Company::factory()->create();

        $company->users()->attach($user1);
        $company->users()->attach($user2);

        $this->assertCount(2, Company::first()->users);

        $company->users()->first()->is($user1);

        $this->postJson(CompanyRepository::route("$company->id/sync/users"), [
            'users' => $user->getKey()
        ])->assertCreated();

        $company->users()->first()->is($user);

        $this->assertCount(1, Company::first()->users);
    }

}
