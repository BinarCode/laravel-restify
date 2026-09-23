<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\RoleRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `RestifyRequest::__get('relatedRepository')` resolves through Laravel's
 * `Arr::get($this->all(), $key, fn () => $this->route($key))`, so a JSON body
 * key named `relatedRepository` shadowed the URL segment of the same name.
 * These guard the fix (route is the single source of truth) both for the
 * request blowing up, and for it silently targeting the wrong related field.
 */
class RelatedRepositoryBodyShadowingTest extends IntegrationTestCase
{
    #[Test]
    public function a_body_relatedrepository_array_does_not_break_attach(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $withoutPoison = $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();

        $withPoison = $this->postJson(CompanyRepository::route("{$company2->id}/attach/users"), [
            'users' => [$user2->getKey()],
            'is_admin' => true,
            'relatedRepository' => ['users'],
        ]);

        $withPoison->assertStatus($withoutPoison->getStatusCode());
        $withPoison->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company2->getKey(),
            'user_id' => $user2->getKey(),
            'is_admin' => true,
        ]);
    }

    #[Test]
    public function a_body_relatedrepository_scalar_does_not_break_sync(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $withoutPoison = $this->postJson(CompanyRepository::route("{$company->id}/sync/users"), [
            'users' => [$user->getKey()],
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();

        $withPoison = $this->postJson(CompanyRepository::route("{$company2->id}/sync/users"), [
            'users' => [$user2->getKey()],
            'relatedRepository' => 5,
        ]);

        $withPoison->assertStatus($withoutPoison->getStatusCode());
        $withPoison->assertOk();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company2->getKey(),
            'user_id' => $user2->getKey(),
        ]);
    }

    #[Test]
    public function a_body_relatedrepository_key_cannot_redirect_the_attach_to_a_different_field(): void
    {
        $company = Company::factory()->create();
        $role = Role::factory()->create(['name' => 'engineering']);

        CompanyRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'users' => BelongsToMany::make('users', UserRepository::class)->withPivot(
                    Field::make('is_admin')->rules('required')
                ),
                'roles' => BelongsToMany::make('roles', RoleRepository::class)
                    ->canAttach(fn ($request, $pivot) => false),
            ]);

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'relatedRepository' => 'roles',
            'roles' => [$role->getKey()],
        ]);

        $this->assertDatabaseCount(CompanyRolePivot::class, 0);
    }
}
