<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ActiveBooleanFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CreatedAfterDateFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\InactiveFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\SelectCategoryFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryFilterControllerTest extends IntegrationTest
{
    public function test_can_get_available_filters()
    {
        $this->get(PostRepository::uriKey() . '/filters')
            ->assertJsonCount(4, 'data');
    }

    public function test_available_filters_contains_matches_sortables_searches()
    {
        PostRepository::$match = [
            'title' => 'text',
        ];

        PostRepository::$sort = [
            'title',
        ];

        PostRepository::$search = [
            'id',
            'title',
        ];

        $response = $this->getJson('posts/filters?include=matches,sortables,searchables')
            // 5 custom filters
            // 1 match filter
            // 1 sort
            // 2 searchable
            ->assertJsonCount(8, 'data');

        $this->assertSame(
            $response->json('data.4.type'), MatchFilter::TYPE
        );
        $this->assertSame(
            $response->json('data.4.column'), 'title'
        );
        $this->assertSame(
            $response->json('data.5.type'), SortableFilter::TYPE
        );
        $this->assertSame(
            $response->json('data.5.column'), 'title'
        );
        $this->assertSame(
            $response->json('data.6.type'), SearchableFilter::TYPE
        );
        $this->assertSame(
            $response->json('data.6.column'), 'id'
        );
    }

    public function test_available_filters_returns_only_matches_sortables_searches()
    {
        PostRepository::$match = [
            'title' => 'text',
        ];

        PostRepository::$sort = [
            'title' => SortableFilter::make()->setColumn('posts.title'),
        ];

        PostRepository::$search = [
            'id',
            'title',
        ];

//        $response = $this->getJson('posts/filters?only=matches,sortables,searchables')
//            ->assertJsonCount(4, 'data');

//        $response = $this->getJson('posts/filters?only=matches')
//            ->assertJsonCount(1, 'data');
//
        $response = $this->getJson('posts/filters?only=sortables')
            ->dump()
            ->assertJsonCount(1, 'data');

        $response = $this->getJson('posts/filters?only=searchables')
            ->assertJsonCount(2, 'data');
    }

    public function test_value_filter_doesnt_require_value()
    {
        factory(Post::class)->create(['is_active' => false]);
        factory(Post::class)->create(['is_active' => true]);

        $filters = base64_encode(json_encode([
            [
                'key' => InactiveFilter::uriKey()
            ],
        ]));

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('posts?filters=' . $filters)
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_boolean_filter_is_applied()
    {
        factory(Post::class)->create(['is_active' => false]);
        factory(Post::class)->create(['is_active' => true]);

        $availableFilters = $this
            ->get(PostRepository::uriKey() . '/filters?include=matches')
            ->dump()
            ->assertOk()
            ->assertJsonFragment($booleanFilter = [
                'key' => $key = 'active-booleans',
                'type' => 'boolean',
                'advanced' => true,
            ]);

        $filters = base64_encode(json_encode([
            [
                'key' => $key,
                'value' => [
                    'is_active' => false,
                ],
            ],
        ]));

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('posts?filters=' . $filters)
            ->dump()
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_select_filter_is_applied()
    {
        factory(Post::class)->create(['category' => 'movie']);
        factory(Post::class)->create(['category' => 'article']);

        $filters = base64_encode(json_encode([
            [
                'key' => SelectCategoryFilter::uriKey(),
                'value' => 'article',
            ],
        ]));

        $this->getJson('posts?filters=' . $filters)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_the_timestamp_filter_is_applied()
    {
        factory(Post::class)->create(['created_at' => now()->addYear()]);
        factory(Post::class)->create(['created_at' => now()->subYear()]);

        $filters = base64_encode(json_encode([
            [
                'key' => UserRepository::uriKey(),
                'value' => now()->addWeek()->timestamp,
            ],
            [
                'key' => CreatedAfterDateFilter::uriKey(),
                'value' => [
                    'created_at' => now()->addWeek()->timestamp,
                ],
            ],
        ]));

        $this->get('posts?filters=' . $filters)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
