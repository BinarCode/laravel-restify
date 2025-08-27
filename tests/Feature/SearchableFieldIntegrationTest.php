<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class SearchableFieldIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_field_searchable_closure_works(): void
    {
        Post::factory()->create(['title' => 'Laravel Framework']);
        Post::factory()->create(['title' => 'Vue.js Guide']);
        Post::factory()->create(['title' => 'PHP Basics']);

        Restify::repositories([
            TestSearchableRepository::class,
        ]);

        $response = $this->getJson(TestSearchableRepository::route(query: ['search' => 'Framework']));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_field_searchable_with_custom_search_filter_works(): void
    {
        Post::factory()->create(['title' => 'Laravel Framework']);
        Post::factory()->create(['title' => 'Vue.js Guide']);
        Post::factory()->create(['title' => 'PHP Basics']);

        Restify::repositories([
            TestCustomSearchFilterRepository::class,
        ]);

        $response = $this->getJson(TestCustomSearchFilterRepository::route(query: ['search' => 'Framework']));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_field_searchable_with_invokable_class_works(): void
    {
        Post::factory()->create(['title' => 'Laravel Framework']);
        Post::factory()->create(['title' => 'Vue.js Guide']);
        Post::factory()->create(['title' => 'PHP Basics']);

        Restify::repositories([
            TestInvokableSearchRepository::class,
        ]);

        $response = $this->getJson(TestInvokableSearchRepository::route(query: ['search' => 'vue']));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_field_searchable_basic_column_works(): void
    {
        Post::factory()->create(['title' => 'Laravel Framework']);
        Post::factory()->create(['title' => 'Vue.js Guide']);

        Restify::repositories([
            TestBasicSearchRepository::class,
        ]);

        $response = $this->getJson(TestBasicSearchRepository::route(query: ['search' => 'Laravel']));

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}

class TestSearchableRepository extends Repository
{
    public static $model = Post::class;

    public static array $search = [];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->searchable(function ($request, $query, $value) {
                $query->orWhere('title', 'LIKE', "%{$value}%");
            }),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [];
    }
}

class TestCustomSearchFilterRepository extends Repository
{
    public static $model = Post::class;
    public static array $search = [];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->searchable(new CustomTitleSearchFilter),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [];
    }
}

class CustomTitleSearchFilter extends SearchableFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        return $query->orWhere('title', 'LIKE', "%{$value}%");
    }
}

class TestInvokableSearchRepository extends Repository
{
    public static $model = Post::class;
    public static array $search = [];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->searchable(new InvokableSearchFilter),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [];
    }
}

class InvokableSearchFilter
{
    public function __invoke($request, $query, $value)
    {
        $query->orWhere('title', 'LIKE', "%{$value}%");
    }
}

class TestBasicSearchRepository extends Repository
{
    public static $model = Post::class;
    public static array $search = [];

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->searchable(),
        ];
    }

    public function filters(RestifyRequest $request): array
    {
        return [];
    }
}
