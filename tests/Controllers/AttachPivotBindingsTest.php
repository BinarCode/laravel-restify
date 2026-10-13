<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Label\Label;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class AttachPivotBindingsTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    #[Test]
    #[TestWith(['05'], 'zero padded')]
    #[TestWith(['5'], 'string digits')]
    #[TestWith([5], 'int')]
    public function it_binds_the_real_integer_key_to_the_pivot_insert(int|string $requestedId): void
    {
        $company = Company::factory()->create();
        User::factory()->create(['id' => 5]);

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/members"), [
            'members' => [$requestedId],
        ])->assertCreated();

        $this->assertSame([$company->getKey(), 5], array_slice($this->pivotInsertBindings('company_user'), 0, 2));
    }

    #[Test]
    public function it_binds_the_real_string_key_to_the_pivot_insert(): void
    {
        $company = Company::factory()->create();
        Label::query()->create(['code' => 'abc-uuid']);

        $this->recordQueries();

        $this->postJson(CompanyRepository::route("{$company->id}/attach/labels"), [
            'labels' => ['ABC-UUID'],
        ])->assertCreated();

        $this->assertSame([$company->getKey(), 'abc-uuid'], array_slice($this->pivotInsertBindings('company_label'), 0, 2));
    }

    /**
     * @return list<mixed>
     */
    private function pivotInsertBindings(string $table): array
    {
        foreach (DB::getQueryLog() as $entry) {
            if (str_starts_with($entry['query'], 'insert into "'.$table.'"')) {
                return array_values($entry['bindings']);
            }
        }

        $this->fail("No insert into [{$table}] was executed.");
    }
}
