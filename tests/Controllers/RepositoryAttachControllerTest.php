<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryAttachControllerTest extends IntegrationTest
{
    public function test_attach_a_user_to_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();

        $response = $this->postJson('restify-api/companies/'.$company->id.'/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertStatus(201);

        $response->assertJsonFragment([
            'company_id' => '1',
            'user_id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_attach_multiple_users_to_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(0, $usersFromCompany->json('data'));

        $response = $this->postJson('restify-api/companies/'.$company->id.'/attach/users', [
            'users' => [1, 2],
            'is_admin' => true,
        ])
            ->assertStatus(201);

        $response->assertJsonFragment([
            'company_id' => '1',
            'user_id' => $user->id,
            'is_admin' => true,
        ]);

        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(2, $usersFromCompany->json('data'));
    }

    public function test_after_attach_a_user_to_company_number_of_users_increased()
    {
        $user = $this->mockUsers()->first();
        $company = factory(Company::class)->create();

        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(0, $usersFromCompany->json('data'));

        $this->postJson('restify-api/companies/'.$company->id.'/attach/users', [
            'users' => $user->id,
        ]);

        $usersFromCompany = $this->getJson('/restify-api/users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(1, $usersFromCompany->json('data'));
    }
}
