<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\TrashableUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\TrashableUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AttachRespectsRelatedScopesTest extends IntegrationTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([TrashableUserRepository::class]);

        $this->company = Company::factory()->create();
    }

    #[Test]
    #[TestWith(['attach', JsonResponse::HTTP_CREATED], 'attach')]
    #[TestWith(['sync', JsonResponse::HTTP_OK], 'sync')]
    public function it_does_not_link_a_related_record_a_global_scope_hides(string $action, int $status): void
    {
        $visible = User::factory()->create(['name' => 'visible']);
        $hidden = User::factory()->create(['name' => 'hidden']);

        User::addGlobalScope('hidden', fn (Builder $query) => $query->where('name', '!=', 'hidden'));

        try {
            $this->postJson(CompanyRepository::route("{$this->company->id}/{$action}/staff"), [
                'staff' => [$visible->getKey(), $hidden->getKey()],
            ])->assertNotFound();

            $this->assertDatabaseCount(CompanyUserPivot::class, 0);

            $this->postJson(CompanyRepository::route("{$this->company->id}/{$action}/staff"), [
                'staff' => [$visible->getKey()],
            ])->assertStatus($status);

            $this->assertDatabaseCount(CompanyUserPivot::class, 1);

            $this->assertDatabaseHas(CompanyUserPivot::class, [
                'company_id' => $this->company->getKey(),
                'user_id' => $visible->getKey(),
            ]);
        } finally {
            User::clearBootedModels();
        }
    }

    #[Test]
    #[TestWith(['attach'], 'attach')]
    #[TestWith(['sync'], 'sync')]
    public function it_does_not_link_a_soft_deleted_related_record(string $action): void
    {
        $active = TrashableUser::query()->findOrFail(User::factory()->create()->getKey());
        $trashed = TrashableUser::query()->findOrFail(User::factory()->create()->getKey());
        $trashed->delete();

        $this->postJson(CompanyRepository::route("{$this->company->id}/{$action}/trashableStaff"), [
            'trashableStaff' => [$active->getKey(), $trashed->getKey()],
        ])->assertNotFound();

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }

    #[Test]
    public function it_detaches_a_soft_deleted_related_record(): void
    {
        $trashed = TrashableUser::query()->findOrFail(User::factory()->create()->getKey());
        $this->company->trashableStaff()->attach($trashed);
        $trashed->delete();

        $this->postJson(CompanyRepository::route("{$this->company->id}/detach/trashableStaff"), [
            'trashableStaff' => [$trashed->getKey()],
        ])->assertNoContent();

        $this->assertDatabaseMissing(CompanyUserPivot::class, [
            'company_id' => $this->company->getKey(),
            'user_id' => $trashed->getKey(),
        ]);
    }

    #[Test]
    public function it_does_not_detach_a_related_record_a_global_scope_hides(): void
    {
        $hidden = User::factory()->create(['name' => 'hidden']);
        $this->company->staff()->attach($hidden);

        User::addGlobalScope('hidden', fn (Builder $query) => $query->where('name', '!=', 'hidden'));

        try {
            $this->postJson(CompanyRepository::route("{$this->company->id}/detach/staff"), [
                'staff' => [$hidden->getKey()],
            ])->assertNotFound();

            $this->assertDatabaseHas(CompanyUserPivot::class, [
                'company_id' => $this->company->getKey(),
                'user_id' => $hidden->getKey(),
            ]);
        } finally {
            User::clearBootedModels();
        }
    }

    #[Test]
    public function a_sync_that_leaves_out_a_related_record_a_global_scope_hides_removes_its_pivot(): void
    {
        $visible = User::factory()->create(['name' => 'visible']);
        $hidden = User::factory()->create(['name' => 'hidden']);
        $this->company->staff()->attach($hidden);

        User::addGlobalScope('hidden', fn (Builder $query) => $query->where('name', '!=', 'hidden'));

        try {
            $this->postJson(CompanyRepository::route("{$this->company->id}/sync/staff"), [
                'staff' => [$visible->getKey()],
            ])->assertOk();

            $this->assertDatabaseCount(CompanyUserPivot::class, 1);

            $this->assertDatabaseHas(CompanyUserPivot::class, [
                'company_id' => $this->company->getKey(),
                'user_id' => $visible->getKey(),
            ]);
        } finally {
            User::clearBootedModels();
        }
    }
}
