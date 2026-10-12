<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyLabelPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRolePivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AttachStoresRealRelatedKeyTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith(['attach', JsonResponse::HTTP_CREATED], 'attach')]
    #[TestWith(['sync', JsonResponse::HTTP_OK], 'sync')]
    public function it_stores_the_real_string_key_when_the_request_differs_in_case(string $action, int $status): void
    {
        $company = Company::factory()->create();
        Label::query()->create(['code' => 'abc-uuid']);

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/labels"), [
            'labels' => ['ABC-UUID'],
        ])->assertStatus($status);

        $this->assertDatabaseCount(CompanyLabelPivot::class, 1);

        $this->assertSame(['abc-uuid'], $this->storedLabelCodes());
    }

    #[Test]
    #[TestWith(['05', 'attach', JsonResponse::HTTP_CREATED], 'zero padded, attach')]
    #[TestWith(['05', 'sync', JsonResponse::HTTP_OK], 'zero padded, sync')]
    #[TestWith(['5', 'attach', JsonResponse::HTTP_CREATED], 'string digits, attach')]
    #[TestWith([5, 'attach', JsonResponse::HTTP_CREATED], 'int, attach')]
    public function it_stores_the_real_integer_key_for_any_spelling_of_the_id(int|string $requestedId, string $action, int $status): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['id' => 5]);

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/members"), [
            'members' => [$requestedId],
        ])->assertStatus($status);

        $this->assertDatabaseCount(CompanyUserPivot::class, 1);

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }

    #[Test]
    #[TestWith(['05', 'attach', JsonResponse::HTTP_CREATED], 'zero padded, attach')]
    #[TestWith(['05', 'sync', JsonResponse::HTTP_OK], 'zero padded, sync')]
    #[TestWith(['5', 'attach', JsonResponse::HTTP_CREATED], 'string digits, attach')]
    #[TestWith([5, 'attach', JsonResponse::HTTP_CREATED], 'int, attach')]
    public function it_resolves_a_non_primary_related_key_for_any_spelling_of_the_id(int|string $requestedId, string $action, int $status): void
    {
        $company = Company::factory()->create();
        Role::factory()->create(['id' => 5, 'name' => 'editor']);

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/roles"), [
            'roles' => [$requestedId],
        ])->assertStatus($status);

        $this->assertDatabaseCount(CompanyRolePivot::class, 1);

        $this->assertDatabaseHas(CompanyRolePivot::class, [
            'company_id' => $company->getKey(),
            'role_name' => 'editor',
        ]);
    }

    #[Test]
    public function it_detaches_a_non_primary_related_key_for_a_non_canonical_id(): void
    {
        $company = Company::factory()->create();
        Role::factory()->create(['id' => 5, 'name' => 'editor']);
        $company->roles()->attach('editor');

        $this->postJson(CompanyRepository::route("{$company->id}/detach/roles"), [
            'roles' => ['05'],
        ])->assertNoContent();

        $this->assertDatabaseCount(CompanyRolePivot::class, 0);
    }

    #[Test]
    #[TestWith([['99']], 'unknown id')]
    #[TestWith([['05', '99']], 'known and unknown id')]
    public function it_never_attaches_when_an_id_is_not_returned_by_the_database(array $ids): void
    {
        $company = Company::factory()->create();
        Role::factory()->create(['id' => 5, 'name' => 'editor']);

        $this->postJson(CompanyRepository::route("{$company->id}/attach/roles"), [
            'roles' => $ids,
        ])->assertNotFound();

        $this->assertDatabaseCount(CompanyRolePivot::class, 0);
    }

    /**
     * @return list<string>
     */
    private function storedLabelCodes(): array
    {
        return DB::table('company_label')->pluck('label_code')->all();
    }
}
