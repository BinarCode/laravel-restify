<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryAttachControllerTest extends IntegrationTest
{
    public function test_attach_a_user_to_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();

        $response = $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])->assertStatus(201);

        $response->assertJsonFragment([
            'company_id' => '1',
            'user_id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_pivot_field_validation()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();

        $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => $user->id,
        ])
            ->assertStatus(400);
    }

    public function test_attach_multiple_users_to_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(0, $usersFromCompany->json('data'));

        $response = $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => [1, 2],
            'is_admin' => true,
        ])
            ->assertStatus(201);

        $response->assertJsonFragment([
            'company_id' => '1',
            'user_id' => $user->id,
            'is_admin' => true,
        ]);

        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(2, $usersFromCompany->json('data'));
    }

    public function test_after_attach_a_user_to_company_number_of_users_increased()
    {
        $user = $this->mockUsers()->first();
        $company = factory(Company::class)->create();

        $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users')
            ->assertJsonCount(0, 'data');

        $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ]);

        $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users')
            ->assertJsonCount(1, 'data');
    }

    public function test_policy_to_attach_a_user_to_a_company()
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $this->authenticate(
            factory(User::class)->create()
        );

        $_SERVER['allow_attach_users'] = false;

        $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertForbidden();

        $_SERVER['allow_attach_users'] = true;

        $this->postJson('companies/' . $company->id . '/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertCreated();
    }
}
