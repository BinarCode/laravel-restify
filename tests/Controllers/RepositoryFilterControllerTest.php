<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\ActiveBooleanFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\CreatedAfterDateFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\SelectCategoryFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class RepositoryFilterControllerTest extends IntegrationTest
{
    public function test_can_get_available_filters()
    {
        $response = $this
            ->withoutExceptionHandling()
            ->getJson('restify-api/posts/filters')
            ->dump()
            ->getContent();

        dd($response);

        $this->assertCount(3, $response->json('data'));
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
            ->getJson('restify-api/posts?filters='.$filters)
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
            ->getJson('restify-api/posts?filters='.$filters)
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
            ->getJson('restify-api/posts')
            ->assertStatus(200);

        $this->assertCount(1, $response->json('data'));
    }
}
