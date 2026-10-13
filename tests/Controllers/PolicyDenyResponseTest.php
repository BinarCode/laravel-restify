<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class PolicyDenyResponseTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Gate::policy(Company::class, CompanyPolicy::class);

        $this->authenticate(User::factory()->create());
    }

    #[Test]
    public function attach_surfaces_the_policy_deny_message(): void
    {
        $_SERVER['allow_attach_users'] = Response::deny('Employee belongs to another company.');

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/attach/users"), [
            'users' => $user->getKey(),
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Employee belongs to another company.');

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function attach_keeps_the_status_set_by_the_policy(): void
    {
        $_SERVER['allow_attach_users'] = Response::denyAsNotFound('Employee not found.');

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/attach/users"), [
            'users' => $user->getKey(),
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Employee not found.');

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function attach_keeps_the_generic_message_when_the_policy_returns_false(): void
    {
        $_SERVER['allow_attach_users'] = false;

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/attach/users"), [
            'users' => $user->getKey(),
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'You cannot attach model:'.User::class.', to the model:'.Company::class.', check your permissions.'
            );
    }

    #[Test]
    public function sync_surfaces_the_policy_deny_message(): void
    {
        $_SERVER['allow_sync_users'] = Response::deny('Employees belong to another company.');

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Employees belong to another company.');

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function sync_keeps_the_status_set_by_the_policy(): void
    {
        $_SERVER['allow_sync_users'] = Response::denyWithStatus(404, 'Employees not found.');

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Employees not found.');
    }

    #[Test]
    public function sync_keeps_the_generic_message_when_the_policy_returns_false(): void
    {
        $_SERVER['allow_sync_users'] = false;

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->getKey()}/sync/users"), [
            'users' => [$user->getKey()],
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'You cannot sync key to the model:'.Company::class.', check your permissions.');
    }

    #[Test]
    public function detach_surfaces_the_policy_deny_message(): void
    {
        $_SERVER['allow_detach_users'] = Response::deny('Employee cannot be removed.');

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->users()->attach($user->getKey());

        $this->postJson(CompanyRepository::route("{$company->getKey()}/detach/users"), [
            'users' => [$user->getKey()],
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Employee cannot be removed.');

        $this->assertDatabaseCount(CompanyUserPivot::class, 1);
    }

    #[Test]
    public function detach_keeps_the_status_set_by_the_policy(): void
    {
        $_SERVER['allow_detach_users'] = Response::denyAsNotFound('Employee not found.');

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->users()->attach($user->getKey());

        $this->postJson(CompanyRepository::route("{$company->getKey()}/detach/users"), [
            'users' => [$user->getKey()],
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Employee not found.');

        $this->assertDatabaseCount(CompanyUserPivot::class, 1);
    }

    #[Test]
    public function detach_keeps_the_default_response_when_the_policy_returns_false(): void
    {
        $_SERVER['allow_detach_users'] = false;

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->users()->attach($user->getKey());

        $this->postJson(CompanyRepository::route("{$company->getKey()}/detach/users"), [
            'users' => [$user->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseCount(CompanyUserPivot::class, 1);
    }
}
