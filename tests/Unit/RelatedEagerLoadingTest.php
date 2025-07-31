<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Eager\Related;
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class RelatedEagerLoadingTest extends IntegrationTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_uses_eager_loaded_relation_when_available(): void
    {
        $company = Company::factory()->create(['name' => 'Eager Company']);
        $user = User::factory()->for($company)->create();
        
        // Manually load the relation
        $user->load('company');
        
        $repository = UserRepository::resolveWith($user);
        $related = new Related('company');
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify it returns the already loaded company
        $this->assertInstanceOf(Company::class, $related->getValue());
        $this->assertEquals('Eager Company', $related->getValue()->name);
        
        // Verify the relation was already loaded (no new query)
        $this->assertTrue($user->relationLoaded('company'));
    }

    #[Test]
    public function it_makes_query_when_relation_not_loaded(): void
    {
        $company = Company::factory()->create(['name' => 'Lazy Company']);
        $user = User::factory()->for($company)->create();
        
        // Do NOT load the relation
        $this->assertFalse($user->relationLoaded('company'));
        
        $repository = UserRepository::resolveWith($user);
        $related = new Related('company');
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify it still returns the company (via query)
        $this->assertInstanceOf(Company::class, $related->getValue());
        $this->assertEquals('Lazy Company', $related->getValue()->name);
    }

    #[Test]
    public function it_handles_collection_relations_when_eager_loaded(): void
    {
        $user = User::factory()->create();
        $posts = \Binaryk\LaravelRestify\Tests\Fixtures\Post\Post::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);
        
        // Manually load the posts relation
        $user->load('posts');
        
        $repository = UserRepository::resolveWith($user);
        $related = new Related('posts');
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify it returns the collection
        $this->assertInstanceOf(Collection::class, $related->getValue());
        $this->assertCount(3, $related->getValue());
        $this->assertTrue($user->relationLoaded('posts'));
    }

    #[Test]
    public function it_handles_null_relations(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        
        // Load the empty relation
        $user->load('company');
        
        $repository = UserRepository::resolveWith($user);
        $related = new Related('company');
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify it handles null correctly
        $this->assertNull($related->getValue());
    }


    #[Test]
    public function it_handles_eager_field_relations(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        
        $repository = UserRepository::resolveWith($user);
        
        // Create an eager field
        $eagerField = BelongsTo::make('company', CompanyRepository::class);
        $related = new Related('company', $eagerField);
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify it's recognized as eager
        $this->assertTrue($related->isEager());
    }

    #[Test]
    public function it_handles_nested_relations_with_dot_notation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->for($company)->create();
        $posts = \Binaryk\LaravelRestify\Tests\Fixtures\Post\Post::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);
        
        // Load nested relation
        $user->load('posts.user');
        
        $repository = UserRepository::resolveWith($user);
        $related = new Related('posts.user');
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // For nested relations, it returns the first level
        $value = $related->getValue();
        $this->assertIsArray($value);
    }

    #[Test]
    public function it_uses_custom_resolver_callback(): void
    {
        $user = User::factory()->create();
        $repository = UserRepository::resolveWith($user);
        
        $related = new Related('custom');
        $related->resolveUsing(function ($request, $repo) {
            $this->assertInstanceOf(RestifyRequest::class, $request);
            $this->assertInstanceOf(UserRepository::class, $repo);
            return 'custom-value';
        });
        
        $request = Mockery::mock(RestifyRequest::class);
        $request->shouldReceive('related->resolved')->once();
        
        // Resolve the related field
        $related->resolve($request, $repository);
        
        // Verify custom resolver was used
        $this->assertEquals('custom-value', $related->getValue());
    }

    #[Test]
    public function search_with_join_optimization_reduces_query_count_when_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);
        
        // Create test data
        $companies = Company::factory()->count(5)->create();
        foreach ($companies as $company) {
            User::factory()->count(10)->for($company)->create();
        }

        // Configure repository with searchable BelongsTo relationship
        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];

        \Illuminate\Support\Facades\DB::enableQueryLog();
        
        // Perform search that would trigger related field search
        $response = $this->getJson(UserRepository::route(query: [
            'search' => $companies->first()->name,
            'perPage' => 50
        ]));

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $response->assertSuccessful();
        
        // With JOIN optimization, we should have minimal queries:
        // 1. Main search query with JOIN
        // 2. Potentially count query
        $this->assertLessThan(4, count($queries), 
            'Expected fewer than 4 queries with JOIN optimization, got: ' . count($queries)
        );

        // Verify that the main query uses JOIN when optimization is enabled
        $searchQuery = collect($queries)->first(function ($query) use ($companies) {
            return str_contains(strtolower($query['query']), strtolower($companies->first()->name));
        });

        $this->assertNotNull($searchQuery, 'Search query should be found');
        $this->assertStringContainsString('left join', strtolower($searchQuery['query']), 
            'Search query should use LEFT JOIN when optimization is enabled'
        );
        
        // Clean up
        UserRepository::$search = [];
        UserRepository::$related = [];
        config(['restify.search.use_joins' => false]);
    }

    #[Test]
    public function joined_relationships_excluded_from_eager_loading_when_optimization_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);
        
        $company = Company::factory()->create(['name' => 'TestCompany']);
        User::factory()->count(10)->for($company)->create();

        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];
        UserRepository::$with = ['company']; // This should be optimized away when JOINs are enabled

        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->getJson(UserRepository::route(query: [
            'search' => 'TestCompany',
            'related' => 'company',
            'perPage' => 10
        ]));

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $response->assertSuccessful();

        // Check that there's no separate eager loading query for company
        // since it's already joined in the main query when optimization is enabled
        $eagerLoadingQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from "companies"') &&
                   str_contains($query['query'], 'where "companies"."id" in');
        });

        $this->assertCount(0, $eagerLoadingQueries, 
            'Should not execute separate eager loading query for joined relationship when optimization is enabled'
        );
        
        // Clean up
        UserRepository::$search = [];
        UserRepository::$related = [];
        UserRepository::$with = [];
        config(['restify.search.use_joins' => false]);
    }
}