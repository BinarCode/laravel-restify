<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Testing\Fluent\AssertableJson;

class MatchableFieldIntegrationTest extends IntegrationTestCase
{
    public function test_field_matchable_functionality_works(): void
    {
        $repository = new PostRepository();
        $request = new RestifyRequest();

        // Test that field matches are collected
        $fieldMatches = PostRepository::collectFieldMatches($request, $repository);
        $this->assertGreaterThan(0, $fieldMatches->count(), 'Field matches should be collected');

        // Test field instances have matchable functionality
        $fields = $repository->fields($request);

        $titleField = collect($fields)->first(fn($field) => $field->getAttribute() === 'title');
        $this->assertTrue($titleField->isMatchable(), 'Title field should be matchable');
        $this->assertEquals('title', $titleField->getMatchColumn());
        $this->assertEquals('text', $titleField->getMatchType());

        $isActiveField = collect($fields)->first(fn($field) => $field->getAttribute() === 'is_active');
        $this->assertTrue($isActiveField->isMatchable(), 'Is active field should be matchable');
        $this->assertEquals('is_active', $isActiveField->getMatchColumn());
        $this->assertEquals('bool', $isActiveField->getMatchType());
    }

    public function test_field_matchable_closure_works(): void
    {
        // Clear any existing posts to start fresh
        Post::query()->delete();
        
        // Register the test repository
        Restify::repositories([
            TestMatchableRepository::class,
        ]);

        // Create test data with different titles
        $post1 = Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'description' => 'A comprehensive guide to Laravel',
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Vue.js Tutorial',
            'description' => 'Learning Vue.js from scratch',
        ]);

        $post3 = Post::factory()->create([
            'title' => 'Framework Comparison',
            'description' => 'Comparing different frameworks',
        ]);

        // First test: check that we can access the repository without filtering
        $this->getJson(TestMatchableRepository::route())
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 3) // Should have 3 posts total
                    ->etc()
            );

        // Test that matching works with the custom closure
        // Search for 'Framework' - should match post1 and post3
        $this->getJson(TestMatchableRepository::route(query: ['title' => 'Framework']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 2) // Should return exactly 2 posts matching "Framework"
                    ->has('data.0.attributes.title')
                    ->has('data.1.attributes.title')
                    ->where('data.0.attributes.title', fn($title) => in_array($title, [$post1->title, $post3->title]))
                    ->where('data.1.attributes.title', fn($title) => in_array($title, [$post1->title, $post3->title]))
                    ->etc()
            );

        // Test case insensitive search - search for 'laravel' should match post1  
        $this->getJson(TestMatchableRepository::route(query: ['title' => 'laravel']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 1) // Should return exactly 1 post matching "laravel"
                    ->where('data.0.attributes.title', $post1->title) // Laravel Framework Guide
                    ->etc()
            );

        // Test no matches - search for something that doesn't exist
        $this->getJson(TestMatchableRepository::route(query: ['title' => 'NonExistent']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 0) // Should have 0 results for non-existent search
                    ->etc()
            );
    }

    public function test_field_matchable_with_custom_match_filter_works(): void
    {
        // Clear any existing posts to start fresh
        Post::query()->delete();
        
        // Register the test repository
        Restify::repositories([
            TestMatchFilterRepository::class,
        ]);

        // Create test data with different titles
        $post1 = Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'description' => 'A comprehensive guide to Laravel',
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Vue.js Tutorial',
            'description' => 'Learning Vue.js from scratch',
        ]);

        $post3 = Post::factory()->create([
            'title' => 'Framework Comparison',
            'description' => 'Comparing different frameworks',
        ]);

        // First test: check that we can access the repository without filtering
        $this->getJson(TestMatchFilterRepository::route())
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 3) // Should have 3 posts total
                    ->etc()
            );

        // Test that matching works with the custom MatchFilter
        // The CustomTitleMatchFilter searches for titles that START with the given value
        // Search for 'Laravel' - should match only post1 (Laravel Framework Guide)
        $this->getJson(TestMatchFilterRepository::route(query: ['title' => 'Laravel']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 1) // Should return exactly 1 post starting with "Laravel"
                    ->where('data.0.attributes.title', $post1->title) // Laravel Framework Guide
                    ->etc()
            );

        // Test with 'Framework' - should match only post3 (Framework Comparison)
        // Note: This is different from the closure test which used LIKE %value%
        $this->getJson(TestMatchFilterRepository::route(query: ['title' => 'Framework']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 1) // Should return exactly 1 post starting with "Framework"
                    ->where('data.0.attributes.title', $post3->title) // Framework Comparison
                    ->etc()
            );

        // Test with 'Vue' - should match only post2
        $this->getJson(TestMatchFilterRepository::route(query: ['title' => 'Vue']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 1) // Should return exactly 1 post starting with "Vue"
                    ->where('data.0.attributes.title', $post2->title) // Vue.js Tutorial
                    ->etc()
            );
        
        // Test no matches - search for something that doesn't exist
        $this->getJson(TestMatchFilterRepository::route(query: ['title' => 'NonExistent']))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('data', 0) // Should have 0 results for non-existent search
                    ->etc()
            );
    }

    public function test_matchable_field_aliases_work(): void
    {
        $field = field('name');

        // Test text alias
        $field->matchableText();
        $this->assertTrue($field->isMatchable());
        $this->assertEquals('text', $field->getMatchType());

        // Test boolean alias
        $field->matchableBool();
        $this->assertEquals('bool', $field->getMatchType());

        // Test integer alias
        $field->matchableInteger();
        $this->assertEquals('integer', $field->getMatchType());

        // Test datetime alias
        $field->matchableDatetime();
        $this->assertEquals('datetime', $field->getMatchType());

        // Test between alias
        $field->matchableBetween();
        $this->assertEquals('between', $field->getMatchType());

        // Test array alias
        $field->matchableArray();
        $this->assertEquals('array', $field->getMatchType());
    }
}

class TestMatchableRepository extends Repository
{
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title')->matchable(function ($request, $query, $value) {
                // Custom closure that searches for titles containing the value (case insensitive)
                $query->where('title', 'like', "%{$value}%");
            }),
            Field::make('description'),
        ];
    }

    public static function uriKey(): string
    {
        return 'test-matchable';
    }
}

class CustomTitleMatchFilter extends MatchFilter
{
    public function __construct()
    {
        parent::__construct();
        $this->setColumn('title');
    }

    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        // Custom filtering logic: search for titles that start with the given value
        $query->where('title', 'like', "{$value}%");
        
        return $query;
    }
}

class TestMatchFilterRepository extends Repository
{
    public static string $model = Post::class;

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title')->matchable(new CustomTitleMatchFilter()),
            Field::make('description'),
        ];
    }

    public static function uriKey(): string
    {
        return 'test-match-filter';
    }
}