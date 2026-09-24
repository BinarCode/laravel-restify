<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

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
}
