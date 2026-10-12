<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\User\TrashableUser;
use Binaryk\LaravelRestify\Tests\Fixtures\User\TrashableUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class ResolvesRelatedModelsQueryCountTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    #[Test]
    public function attaching_several_unknown_users_does_not_query_once_per_missing_id(): void
    {
        $company = Company::factory()->create();

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => [9001, 9002, 9003],
            'is_admin' => true,
        ])->assertNotFound();

        $this->assertSelectCount(1, User::class);
    }

    #[Test]
    public function attaching_several_unknown_string_keys_costs_at_most_one_retry_query(): void
    {
        $company = Company::factory()->create();

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/labels"), [
            'labels' => ['missing-1', 'missing-2', 'missing-3'],
        ])->assertNotFound();

        $this->assertSelectCount(2, Label::class);
    }

    #[Test]
    #[TestWith(['attach'], 'attach')]
    #[TestWith(['sync'], 'sync')]
    public function linking_several_users_resolves_them_in_one_lookup(string $action): void
    {
        $company = Company::factory()->create();
        $users = User::factory()->count(3)->create();

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/{$action}/staff"), [
            'staff' => $users->modelKeys(),
        ])->assertSuccessful();

        $this->assertSelectCount(1, User::class);

        $this->assertDatabaseCount(CompanyUserPivot::class, 3);
    }

    #[Test]
    public function detaching_several_soft_deleted_users_resolves_them_in_one_lookup(): void
    {
        Restify::repositories([TrashableUserRepository::class]);

        $company = Company::factory()->create();
        $userKeys = User::factory()->count(3)->create()->modelKeys();
        $company->trashableStaff()->attach($userKeys);
        TrashableUser::query()->whereKey($userKeys)->delete();

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/detach/trashableStaff"), [
            'trashableStaff' => $userKeys,
        ])->assertNoContent();

        $this->assertSelectCount(1, User::class);

        $this->assertDatabaseCount(CompanyUserPivot::class, 0);
    }
}
