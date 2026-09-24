<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class RelatedRepositoryUriKeyMismatchTest extends IntegrationTestCase
{
    #[Test]
    public function it_attaches_a_relation_whose_repository_uri_key_differs_from_its_models_table(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/staff"), [
            'staff' => $user->getKey(),
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    public function it_syncs_a_relation_whose_repository_uri_key_differs_from_its_models_table(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/sync/staff"), [
            'staff' => [$user->getKey()],
        ])->assertOk();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    public function it_detaches_a_relation_whose_repository_uri_key_differs_from_its_models_table(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->staff()->attach($user->getKey());

        $this->postJson(CompanyRepository::route("{$company->id}/detach/staff"), [
            'staff' => [$user->getKey()],
        ])->assertNoContent();

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
