<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class SearchableFilterTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset config to default
        config(['restify.search.use_joins' => false]);
    }

    protected function tearDown(): void
    {
        // Reset config to default
        config(['restify.search.use_joins' => false]);
        
        parent::tearDown();
    }

    #[Test]
    public function it_uses_subqueries_by_default(): void
    {
        // Ensure JOIN optimization is disabled (default)
        $this->assertFalse(config('restify.search.use_joins'));
        
        $company = Company::factory()->create(['name' => 'TestCompany']);
        $user = User::factory()->for($company)->create();
        
        $repository = UserRepository::resolveWith($user);
        $belongsToField = BelongsTo::make('company', CompanyRepository::class)->searchable(['companies.name']);
        
        $filter = SearchableFilter::make()->setRepository($repository)->usingBelongsTo($belongsToField);
        
        $query = User::query();
        $request = Mockery::mock(RestifyRequest::class);
        
        DB::enableQueryLog();
        $filter->filter($request, $query, 'TestCompany');
        $sql = $query->toSql();
        DB::disableQueryLog();
        
        // Should contain subquery pattern
        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('where', strtolower($sql));
        
        // Should NOT contain JOIN
        $this->assertStringNotContainsString('join', strtolower($sql));
    }

    #[Test]
    public function it_uses_joins_when_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);
        $this->assertTrue(config('restify.search.use_joins'));
        
        $company = Company::factory()->create(['name' => 'TestCompany']);
        $user = User::factory()->for($company)->create();
        
        $repository = UserRepository::resolveWith($user);
        $belongsToField = BelongsTo::make('company', CompanyRepository::class)->searchable(['companies.name']);
        
        $filter = SearchableFilter::make()->setRepository($repository)->usingBelongsTo($belongsToField);
        
        $query = User::query();
        $request = Mockery::mock(RestifyRequest::class);
        
        DB::enableQueryLog();
        $filter->filter($request, $query, 'TestCompany');
        $sql = $query->toSql();
        DB::disableQueryLog();
        
        // Should contain JOIN pattern
        $this->assertStringContainsString('left join', strtolower($sql));
        $this->assertStringContainsString('companies_for_company', strtolower($sql));
        
        // Should NOT contain subquery
        $this->assertStringNotContainsString('select "name" from "companies"', strtolower($sql));
    }

    #[Test]
    public function it_handles_direct_fields_without_joins(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);
        
        $user = User::factory()->create(['name' => 'TestUser']);
        $repository = UserRepository::resolveWith($user);
        
        // Create filter for direct field (no BelongsTo)
        $filter = SearchableFilter::make()->setRepository($repository)->setColumn('users.name');
        
        $query = User::query();
        $request = Mockery::mock(RestifyRequest::class);
        
        DB::enableQueryLog();
        $filter->filter($request, $query, 'TestUser');
        $sql = $query->toSql();
        DB::disableQueryLog();
        
        // Should NOT contain JOIN for direct fields
        $this->assertStringNotContainsString('join', strtolower($sql));
        
        // Should contain simple WHERE clause
        $this->assertStringContainsString('where', strtolower($sql));
        $this->assertStringContainsString('like', strtolower($sql));
    }
}