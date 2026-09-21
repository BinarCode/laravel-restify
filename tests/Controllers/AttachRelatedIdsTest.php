<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class AttachRelatedIdsTest extends IntegrationTestCase
{
    #[Test]
    public function attaching_accepts_a_related_id_the_database_matches(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/users"), [
            'users' => ['0'.$user->getKey()],
            'is_admin' => true,
        ])->assertCreated();

        $this->assertDatabaseHas(CompanyUserPivot::class, [
            'company_id' => $company->getKey(),
            'user_id' => $user->getKey(),
        ]);
    }
}
