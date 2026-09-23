<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyDeniedRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `RestifyRequest`'s `relatedRepository`, `viaRelationship` and `repositoryId` used
 * to resolve through Laravel's magic `__get`/`all()`, which reads the JSON/form body
 * before falling back to the route segment. A body key of the same name therefore
 * silently overrode which relation attach/detach/sync actually wrote to, while
 * authorization kept running against the field named by the URL - so a relation
 * with a denying `canAttach` could be bypassed via the field the caller *was*
 * authorized to use. These guard the fix: the route (and the resolved field's own
 * relation) is now the only source of truth.
 */
class AttachRelationFromRouteTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['roles.canDetach.users'],
            $_SERVER['CompanyRepository.attach.repositoryId'],
            $_SERVER['CompanyRepository.detach.repositoryId'],
            $_SERVER['CompanyRepository.sync.repositoryId'],
        );

        parent::tearDown();
    }

    #[Test]
    public function a_body_related_repository_key_cannot_redirect_attach_to_a_different_field(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();
        $role2 = Role::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/attach/users"), [
            'users' => [$user2->getKey()],
            'is_admin' => true,
            'relatedRepository' => 'deniedRoles',
            'deniedRoles' => [$role2->getKey()],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company2->getKey(),
            'user_id' => $user2->getKey(),
            'is_admin' => true,
        ]);

        $this->assertDatabaseCount(CompanyDeniedRolePivot::class, 0);
    }

    #[Test]
    public function a_body_related_repository_key_cannot_redirect_sync_to_a_different_field(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/sync/users"), [
            'users' => [$user->getKey()],
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();
        $role2 = Role::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/sync/users"), [
            'users' => [$user2->getKey()],
            'relatedRepository' => 'deniedRoles',
            'deniedRoles' => [$role2->getKey()],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertOk();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company2->getKey(),
            'user_id' => $user2->getKey(),
        ]);

        $this->assertDatabaseCount(CompanyDeniedRolePivot::class, 0);
    }

    #[Test]
    public function a_body_via_relationship_key_cannot_redirect_attach_to_a_different_relation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();
        $role2 = Role::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/attach/users"), [
            'users' => [$user2->getKey()],
            'is_admin' => true,
            'viaRelationship' => 'deniedRoles',
            'deniedRoles' => [$role2->getKey()],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company2->getKey(),
            'user_id' => $user2->getKey(),
            'is_admin' => true,
        ]);

        $this->assertDatabaseCount(CompanyDeniedRolePivot::class, 0);
    }

    #[Test]
    public function a_body_via_relationship_key_cannot_redirect_detach_to_a_different_relation(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $role = Role::factory()->create();

        $company->users()->attach($user->getKey(), ['is_admin' => true]);
        $company->deniedRoles()->attach($role->getKey());

        $this->postJson(CompanyRepository::route("{$company->id}/detach/users"), [
            'users' => [$user->getKey()],
            'viaRelationship' => 'deniedRoles',
        ])->assertNoContent();

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas(CompanyDeniedRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_id' => $role->getKey(),
        ]);
    }

    #[Test]
    public function an_array_body_related_repository_key_does_not_500_on_attach(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/attach/users"), [
            'users' => [$user2->getKey()],
            'is_admin' => true,
            'relatedRepository' => ['deniedRoles'],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertCreated();
    }

    #[Test]
    public function an_array_body_via_relationship_key_does_not_500_on_attach(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/attach/users"), [
            'users' => [$user2->getKey()],
            'is_admin' => true,
            'viaRelationship' => ['deniedRoles'],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertCreated();
    }

    #[Test]
    public function an_array_body_related_repository_key_does_not_500_on_sync(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $clean = $this->postJson(CompanyRepository::route("{$company->id}/sync/users"), [
            'users' => [$user->getKey()],
        ]);

        $company2 = Company::factory()->create();
        $user2 = User::factory()->create();

        $poisoned = $this->postJson(CompanyRepository::route("{$company2->id}/sync/users"), [
            'users' => [$user2->getKey()],
            'relatedRepository' => ['deniedRoles'],
        ]);

        $poisoned->assertStatus($clean->getStatusCode());
        $poisoned->assertOk();
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_which_parent_the_pivot_is_written_to(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
            'repositoryId' => $otherCompany->getKey(),
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $otherCompany->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_attach_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [$user->getKey()],
            'is_admin' => true,
            'repositoryId' => $otherCompany->getKey(),
        ])->assertCreated();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.attach.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_detach_hook(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();

        $company->users()->attach($user->getKey(), ['is_admin' => true]);

        $this->postJson(CompanyRepository::route("{$company->id}/detach/users"), [
            'users' => [$user->getKey()],
            'repositoryId' => $otherCompany->getKey(),
        ])->assertNoContent();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.detach.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_sync_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/sync/users"), [
            'users' => [$user->getKey()],
            'repositoryId' => $otherCompany->getKey(),
        ])->assertOk();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.sync.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_which_parent_the_pivot_is_deleted_from(): void
    {
        $_SERVER['roles.canDetach.users'] = true;

        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();

        $companyA->users()->attach($user->getKey(), ['is_admin' => true]);
        $companyB->users()->attach($user->getKey(), ['is_admin' => true]);

        $this->postJson(CompanyRepository::route("{$companyA->id}/detach/users"), [
            'users' => [$user->getKey()],
            'repositoryId' => $companyB->getKey(),
        ])->assertNoContent();

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $companyA->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $companyB->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
