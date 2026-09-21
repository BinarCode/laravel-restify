<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AttachAuthorizationTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['allow_attach_users'], $_SERVER['allow_detach_users']);

        parent::tearDown();
    }

    #[Test]
    #[TestWith([0], 'the first related model is denied')]
    #[TestWith([1], 'the second related model is denied')]
    #[TestWith([2], 'the last related model is denied')]
    public function every_related_model_is_authorized_before_any_is_attached(int $deniedIndex): void
    {
        $company = Company::factory()->create();
        $users = User::factory(3)->create();
        $denied = $users[$deniedIndex];

        $_SERVER['allow_attach_users'] = fn (User $candidate): bool => ! $candidate->is($denied);

        $this->postJson(CompanyRepository::route("{$company->getKey()}/attach/users"), [
            'users' => $users->modelKeys(),
            'is_admin' => true,
        ])->assertForbidden();

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    #[TestWith([0], 'the first related model is denied')]
    #[TestWith([1], 'the second related model is denied')]
    #[TestWith([2], 'the last related model is denied')]
    public function every_related_model_is_authorized_before_any_is_detached(int $deniedIndex): void
    {
        $company = Company::factory()->create();
        $users = User::factory(3)->create();
        $denied = $users[$deniedIndex];

        $company->users()->attach($users->modelKeys());

        $_SERVER['allow_detach_users'] = fn (User $candidate): bool => ! $candidate->is($denied);

        $this->postJson(CompanyRepository::route("{$company->getKey()}/detach/users"), [
            'users' => $users->modelKeys(),
        ])->assertForbidden();

        $this->assertDatabaseCount(CompanyUserPivot::class, 3);
    }
}
