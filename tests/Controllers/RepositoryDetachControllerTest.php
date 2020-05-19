<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryDetachControllerTest extends IntegrationTest
{
    public function test_detach_a_user_from_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $company->users()->attach($user->id);
        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(1, $usersFromCompany->json('data'));
        $this->postJson('restify-api/companies/'.$company->id.'/detach/users', [
            'users' => $user->id,
        ])
            ->assertStatus(204);
        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(0, $usersFromCompany->json('data'));
    }

    public function test_detach_multiple_users_from_a_company()
    {
        $users = $this->mockUsers(3);
        $company = factory(Company::class)->create();
        $company->users()->attach($users->pluck('id'));

        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(3, $usersFromCompany->json('data'));

        $this->postJson('restify-api/companies/'.$company->id.'/detach/users', [
            'users' => [1, 2],
        ])
            ->assertStatus(204);

        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(1, $usersFromCompany->json('data'));
    }
}
