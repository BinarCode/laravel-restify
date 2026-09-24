<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `RestifyRequest`'s `repositoryId` used to resolve through Laravel's magic
 * `__get`/`request()` helper, which reads the JSON/query body before falling
 * back to the route segment. A body or query `repositoryId` therefore
 * silently overrode the id handed to the show/update/patch/destroy hooks -
 * even though the model itself stayed scoped to the route - so an override
 * that feeds that id into its own queries (CP's `LineItemRepository` and
 * `AddressRepository` do this for uniqueness checks) could be pointed at a
 * different record. These guard the fix: the route segment is now the only
 * source of truth.
 */
class RepositoryIdFromRouteTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset(
            $_SERVER['CompanyRepository.show.repositoryId'],
            $_SERVER['CompanyRepository.update.repositoryId'],
            $_SERVER['CompanyRepository.patch.repositoryId'],
            $_SERVER['CompanyRepository.destroy.repositoryId'],
        );

        parent::tearDown();
    }

    #[Test]
    public function a_query_repository_id_key_cannot_change_the_repository_id_passed_to_the_show_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $this->getJson(CompanyRepository::route($company, query: [
            'repositoryId' => $otherCompany->getKey(),
        ]))->assertOk();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.show.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_update_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $this->putJson(CompanyRepository::route($company), [
            'name' => 'Updated name',
            'repositoryId' => $otherCompany->getKey(),
        ])->assertOk();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.update.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_patch_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $this->patchJson(CompanyRepository::route($company), [
            'name' => 'Patched name',
            'repositoryId' => $otherCompany->getKey(),
        ])->assertOk();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.patch.repositoryId']);
    }

    #[Test]
    public function a_body_repository_id_key_cannot_change_the_repository_id_passed_to_the_destroy_hook(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $this->deleteJson(CompanyRepository::route($company), [
            'repositoryId' => $otherCompany->getKey(),
        ])->assertNoContent();

        $this->assertSame((string) $company->getKey(), (string) $_SERVER['CompanyRepository.destroy.repositoryId']);
    }
}
