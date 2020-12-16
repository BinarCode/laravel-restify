<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

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
        $this->getJson('posts/filters')->assertJsonCount(4, 'data');
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
            $response->json('data.4.key'), 'matches'
        );
        $this->assertSame(
            $response->json('data.4.column'), 'title'
        );
        $this->assertSame(
            $response->json('data.5.key'), 'sortables'
        );
        $this->assertSame(
            $response->json('data.5.column'), 'title'
        );
        $this->assertSame(
            $response->json('data.6.key'), 'searchables'
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
            'title',
        ];

        PostRepository::$search = [
            'id',
            'title',
        ];

        $response = $this->getJson('posts/filters?only=matches,sortables,searchables')
            ->assertJsonCount(4, 'data');

        $response = $this->getJson('posts/filters?only=matches')
            ->assertJsonCount(1, 'data');

        $response = $this->getJson('posts/filters?only=sortables')
            ->assertJsonCount(1, 'data');

        $response = $this->getJson('posts/filters?only=searchables')
            ->assertJsonCount(2, 'data');
    }

    public function test_available_filters_contains_repository_key()
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

        $response = $this->getJson('posts/filters?only=matches,sortables,searchables')->assertJsonFragment([
            'repository_key' => 'posts',
        ]);

        $this->getJson('posts/filters?only=matches')->assertJsonFragment([
            'repository_key' => 'posts',
        ]);


        $this->getJson('posts/filters?only=sortables')->assertJsonFragment([
            'repository_key' => 'posts',
        ]);

        $this->getJson('posts/filters?only=searchables')->assertJsonFragment([
            'repository_key' => 'posts',
        ]);
    }

    public function test_value_filter_doesnt_require_value()
    {
        factory(Post::class)->create(['is_active' => false]);
        factory(Post::class)->create(['is_active' => true]);

        $filters = base64_encode(json_encode([
            [
                'class' => InactiveFilter::class,
            ],
        ]));

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('posts?filters=' . $filters)
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_boolean_filter_is_applied()
    {
        factory(Post::class)->create(['is_active' => false]);
        factory(Post::class)->create(['is_active' => true]);

        $filters = base64_encode(json_encode([
            [
                'class' => ActiveBooleanFilter::class,
                'value' => [
                    'is_active' => false,
                ],
            ],
        ]));

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('posts?filters=' . $filters)
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_select_filter_is_applied()
    {
        factory(Post::class)->create(['category' => 'movie']);
        factory(Post::class)->create(['category' => 'article']);

        $filters = base64_encode(json_encode([
            [
                'class' => SelectCategoryFilter::class,
                'value' => 'article',
            ],
        ]));

        $response = $this
            ->withExceptionHandling()
            ->getJson('posts?filters=' . $filters)
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_timestamp_filter_is_applied()
    {
        factory(Post::class)->create(['created_at' => now()->addYear()]);
        factory(Post::class)->create(['created_at' => now()->subYear()]);

        $filters = base64_encode(json_encode([
            [
                'class' => UserRepository::class,
                'value' => now()->addWeek()->timestamp,
            ],
            [
                'class' => CreatedAfterDateFilter::class,
                'value' => now()->addWeek()->timestamp,
            ],
        ]));

        $response = $this
            ->withExceptionHandling()
            ->getJson('posts')
            ->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }
}
