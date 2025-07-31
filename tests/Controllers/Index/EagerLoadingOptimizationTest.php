<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class EagerLoadingOptimizationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();

        parent::tearDown();
    }

    #[Test]
    public function it_prevents_n_plus_one_queries_with_single_related_field(): void
    {
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
        ];

        Company::factory()
            ->has(User::factory()->count(50))
            ->create(['name' => 'Test Company']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'related' => 'company',
            'perPage' => 50,
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should have exactly 2 queries:
        // 1. Select users
        // 2. Select companies (eager loaded)
        $this->assertCount(2, $queries, 'Expected 2 queries but got '.count($queries));

        // Verify the second query is an IN query for companies
        $this->assertStringContainsString('where "companies"."id" in', $queries[1]['query']);
    }

    #[Test]
    public function it_prevents_n_plus_one_queries_with_multiple_related_fields(): void
    {
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        Company::factory()
            ->has(
                User::factory()
                    ->has(Post::factory()->count(3))
                    ->count(20)
            )
            ->create();

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'related' => 'company,posts',
            'perPage' => 20,
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should have exactly 3 queries:
        // 1. Select users
        // 2. Select companies (eager loaded)
        // 3. Select posts (eager loaded)
        $this->assertCount(3, $queries, 'Expected 3 queries but got '.count($queries));
    }

    #[Test]
    public function it_handles_nested_relationships_without_n_plus_one(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
        ];

        Company::factory()
            ->has(
                User::factory()
                    ->has(Post::factory()->count(2))
                    ->count(5)
            )
            ->create();

        DB::flushQueryLog();

        $this->getJson(PostRepository::route(query: [
            'related' => 'user.company',
            'perPage' => 10,
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should have exactly 3 queries:
        // 1. Select posts
        // 2. Select users (eager loaded)
        // 3. Select companies (eager loaded)
        $this->assertCount(3, $queries, 'Expected 3 queries but got '.count($queries));
    }

    #[Test]
    public function it_uses_eager_loaded_data_when_relation_already_loaded(): void
    {
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
        ];

        $company = Company::factory()->create(['name' => 'Preloaded Company']);
        $user = User::factory()->for($company)->create();

        // Manually load the relation
        $user->load('company');

        // Mock the repository to use our preloaded user
        UserRepository::partialMock()
            ->shouldReceive('indexQuery')
            ->andReturn(User::where('id', $user->id));

        DB::flushQueryLog();

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'company',
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should have only 1 query (to get the user)
        // The company should not be queried again since it's already loaded
        $this->assertCount(1, $queries, 'Expected 1 query but got '.count($queries));

        // Verify the response still includes the company data
        $response->assertJson([
            'data' => [
                [
                    'relationships' => [
                        'company' => [
                            'attributes' => [
                                'name' => 'Preloaded Company',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function it_handles_empty_relationships_without_errors(): void
    {
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        // Create users without companies or posts
        User::factory()->count(5)->create(['company_id' => null]);

        DB::flushQueryLog();

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'company,posts',
            'perPage' => 5,
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should still attempt to eager load (1 query for users, potentially queries for relations)
        $this->assertGreaterThanOrEqual(1, count($queries));

        // Verify response handles null relationships correctly
        $response->assertJson([
            'data' => [
                [
                    'relationships' => [
                        'company' => null,
                        'posts' => [],
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function it_handles_pagination_with_eager_loading(): void
    {
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class),
        ];

        Company::factory()
            ->has(User::factory()->count(100))
            ->create();

        // Test first page
        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'related' => 'company',
            'perPage' => 20,
            'page' => 1,
        ]))->assertOk();

        $firstPageQueries = count(DB::getQueryLog());

        // Test second page
        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'related' => 'company',
            'perPage' => 20,
            'page' => 2,
        ]))->assertOk();

        $secondPageQueries = count(DB::getQueryLog());

        // Both pages should have the same number of queries (no N+1)
        $this->assertEquals($firstPageQueries, $secondPageQueries);
        $this->assertEquals(2, $secondPageQueries); // Users + Companies
    }

    #[Test]
    public function it_uses_joins_when_optimization_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);

        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];

        $company = Company::factory()->create(['name' => 'TechCorp']);
        User::factory()->for($company)->create(['name' => 'John Doe']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: ['search' => 'TechCorp']))
            ->assertOk();

        $queries = DB::getQueryLog();
        $searchQuery = collect($queries)->first(fn ($query) => str_contains(strtolower($query['query']), 'techcorp'));

        $this->assertNotNull($searchQuery, 'Search query should be executed');

        // Verify JOIN is used instead of subquery
        $sql = strtolower($searchQuery['query']);
        $this->assertStringContainsString('left join', $sql, 'Query should use LEFT JOIN when optimization is enabled');
        $this->assertStringContainsString('companies_for_company', $sql, 'Query should use consistent alias');

        // Verify no subquery is used
        $this->assertStringNotContainsString('select * from "companies" where', $sql, 'Query should not contain subquery when JOINs are enabled');

        // Reset config
        config(['restify.search.use_joins' => false]);
    }

    #[Test]
    public function it_uses_subqueries_when_optimization_disabled(): void
    {
        // Ensure JOIN optimization is disabled (default behavior)
        config(['restify.search.use_joins' => false]);

        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];

        $company = Company::factory()->create(['name' => 'LegacyCorp']);
        User::factory()->for($company)->create(['name' => 'Jane Doe']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: ['search' => 'LegacyCorp']))
            ->assertOk();

        $queries = DB::getQueryLog();
        $searchQuery = collect($queries)->first(fn ($query) => str_contains(strtolower($query['query']), 'legacycorp'));

        $this->assertNotNull($searchQuery, 'Search query should be executed');

        // Verify subquery is used (legacy behavior)
        $sql = strtolower($searchQuery['query']);
        $this->assertStringNotContainsString('left join', $sql, 'Query should not use LEFT JOIN when optimization is disabled');
        $this->assertStringContainsString('select "companies"."name" from "companies"', $sql, 'Query should contain subquery when JOINs are disabled');
    }

    #[Test]
    public function it_does_not_affect_direct_field_searches(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);

        UserRepository::$search = ['name', 'email']; // Direct fields only, no relationships
        UserRepository::$related = [];

        User::factory()->create(['name' => 'DirectSearch', 'email' => 'direct@test.com']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: ['search' => 'DirectSearch']))
            ->assertOk();

        $queries = DB::getQueryLog();
        $searchQuery = collect($queries)->first(fn ($query) => str_contains(strtolower($query['query']), 'directsearch'));

        $this->assertNotNull($searchQuery, 'Search query should be executed');

        // Verify no JOINs are used for direct field searches
        $sql = strtolower($searchQuery['query']);
        $this->assertStringNotContainsString('left join', $sql, 'Direct field searches should not use JOINs');
        $this->assertStringNotContainsString('select', $sql.' from', 'Direct field searches should not contain subqueries');

        // Reset config
        config(['restify.search.use_joins' => false]);
    }

    #[Test]
    public function it_avoids_eager_loading_joined_relationships_when_optimization_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);

        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];
        UserRepository::$with = ['company']; // This should be optimized away when JOINs are enabled

        $company = Company::factory()->create(['name' => 'JoinedCorp']);
        User::factory()->for($company)->create(['name' => 'Jane Doe']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'search' => 'JoinedCorp',
            'related' => 'company',
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should not have separate eager loading query for company since it's already joined
        $companyEagerQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from "companies"') &&
                   str_contains($query['query'], 'where "companies"."id" in');
        });

        $this->assertCount(0, $companyEagerQueries, 'Should not eager load joined relationships when JOIN optimization is enabled');

        // Reset config
        config(['restify.search.use_joins' => false]);
    }

    #[Test]
    public function it_still_eager_loads_when_optimization_disabled(): void
    {
        // Ensure JOIN optimization is disabled (default behavior)
        config(['restify.search.use_joins' => false]);

        UserRepository::$search = ['name'];
        UserRepository::$related = [
            'company' => BelongsTo::make('company', CompanyRepository::class)->searchable([
                'companies.name',
            ]),
        ];
        UserRepository::$with = ['company']; // This should still eager load when JOINs are disabled

        $company = Company::factory()->create(['name' => 'EagerCorp']);
        User::factory()->for($company)->create(['name' => 'Jane Doe']);

        DB::flushQueryLog();

        $this->getJson(UserRepository::route(query: [
            'search' => 'EagerCorp',
            'related' => 'company',
        ]))->assertOk();

        $queries = DB::getQueryLog();

        // Should have separate eager loading query for company since JOINs are disabled
        $companyEagerQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from "companies"') &&
                   str_contains($query['query'], 'where "companies"."id" in');
        });

        $this->assertGreaterThan(0, $companyEagerQueries->count(), 'Should eager load relationships when JOIN optimization is disabled');
    }

    #[Test]
    public function it_handles_multiple_searchable_relations_with_unique_joins_when_enabled(): void
    {
        // Enable JOIN optimization
        config(['restify.search.use_joins' => true]);

        PostRepository::$search = ['title'];
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class)->searchable([
                'users.name',
                'users.email',
            ]),
        ];

        $user = User::factory()->create(['name' => 'SearchUser', 'email' => 'search@test.com']);
        Post::factory()->for($user)->create(['title' => 'Test Post']);

        DB::flushQueryLog();

        $this->getJson(PostRepository::route(query: ['search' => 'SearchUser']))
            ->assertOk();

        $queries = DB::getQueryLog();
        $searchQuery = collect($queries)->first(fn ($query) => str_contains(strtolower($query['query']), 'searchuser'));

        $this->assertNotNull($searchQuery);

        $sql = strtolower($searchQuery['query']);

        // Should have single JOIN for the user relationship when optimization is enabled
        $joinCount = substr_count($sql, 'left join "users"');
        $this->assertEquals(1, $joinCount, 'Should have exactly one JOIN for user relationship when optimization is enabled');

        // Should search on multiple fields from the joined table
        $this->assertStringContainsString('"users_for_user"."name"', $sql);
        $this->assertStringContainsString('"users_for_user"."email"', $sql);

        // Reset config
        config(['restify.search.use_joins' => false]);
    }
}
