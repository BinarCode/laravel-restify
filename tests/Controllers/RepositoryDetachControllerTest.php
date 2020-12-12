<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryDetachControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['roles.canDetach.users'] = true;
    }

    public function test_detach_a_user_from_a_company()
    {
        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $company->users()->attach($user->id);
        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(1, $usersFromCompany->json('data'));
        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => $user->id,
        ])->assertStatus(204);

        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(0, $usersFromCompany->json('data'));
    }

    public function test_detach_multiple_users_from_a_company()
    {
        $users = $this->mockUsers(3);
        $company = factory(Company::class)->create();
        $company->users()->attach($users->pluck('id'));

        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(3, $usersFromCompany->json('data'));

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1, 2],
        ])->assertStatus(204);

        $usersFromCompany = $this->getJson('users?viaRepository=companies&viaRepositoryId=1&viaRelationship=users');
        $this->assertCount(1, $usersFromCompany->json('data'));
    }

    public function test_forbidden_detach_users_from_company()
    {
        $_SERVER['roles.canDetach.users'] = false;

        $users = $this->mockUsers(3);
        $company = factory(Company::class)->create();
        $company->users()->attach($users->pluck('id'));

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => [1, 2],
        ])->assertForbidden();
    }

    public function test_policy_to_detach_a_user_to_a_company()
    {
        Gate::policy(Company::class, CompanyPolicy::class);

        $user = $this->mockUsers(2)->first();
        $company = factory(Company::class)->create();
        $this->authenticate(
            factory(User::class)->create()
        );

        $this->postJson('companies/'.$company->id.'/attach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertCreated();

        $_SERVER['allow_detach_users'] = false;

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertForbidden();

        $_SERVER['allow_detach_users'] = true;

        $this->postJson('companies/'.$company->id.'/detach/users', [
            'users' => $user->id,
            'is_admin' => true,
        ])
            ->assertNoContent();
    }
}