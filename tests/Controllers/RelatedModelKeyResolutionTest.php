<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyLabelPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatedModelKeyResolutionTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['tiers', 'attach', JsonResponse::HTTP_CREATED], 'enum key, attach')]
    #[TestWith(['tiers', 'sync', JsonResponse::HTTP_OK], 'enum key, sync')]
    #[TestWith(['badges', 'attach', JsonResponse::HTTP_CREATED], 'stringable key, attach')]
    #[TestWith(['badges', 'sync', JsonResponse::HTTP_OK], 'stringable key, sync')]
    public function it_resolves_a_related_model_whose_key_is_cast_to_an_object(string $relatedRepository, string $action, int $status): void
    {
        $company = Company::factory()->create();
        Label::query()->create(['code' => 'gold']);

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/{$relatedRepository}"), [
            $relatedRepository => ['gold'],
        ])->assertStatus($status);

        $this->assertDatabaseHas(CompanyLabelPivot::class, [
            'company_id' => $company->getKey(),
            'label_code' => 'gold',
        ]);
    }

    #[Test]
    #[TestWith(['attach', JsonResponse::HTTP_CREATED], 'attach')]
    #[TestWith(['sync', JsonResponse::HTTP_OK], 'sync')]
    public function it_matches_a_non_canonical_numeric_id_for_an_integer_key_type(string $action, int $status): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/members"), [
            'members' => ['0'.$user->getKey()],
        ])->assertStatus($status);

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    #[TestWith(['attach', JsonResponse::HTTP_CREATED], 'attach')]
    #[TestWith(['sync', JsonResponse::HTTP_OK], 'sync')]
    public function it_matches_a_string_key_the_database_collation_matches_in_a_different_case(string $action, int $status): void
    {
        $company = Company::factory()->create();
        Label::query()->create(['code' => 'abc-uuid']);

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/labels"), [
            'labels' => ['ABC-UUID'],
        ])->assertStatus($status);

        $this->assertDatabaseHas(CompanyLabelPivot::class, [
            'company_id' => $company->getKey(),
            'label_code' => 'abc-uuid',
        ]);
    }

    #[Test]
    public function it_still_404s_a_string_key_the_database_does_not_match(): void
    {
        $company = Company::factory()->create();
        Label::query()->create(['code' => 'abc-uuid']);

        $this->postJson(CompanyRepository::route("{$company->id}/attach/labels"), [
            'labels' => ['ABC-UUID', 'missing-uuid'],
        ])->assertNotFound();

        $this->assertDatabaseCount(CompanyLabelPivot::class, 0);
    }
}
