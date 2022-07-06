<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class GlobalSearchControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_global_search_returns_matches(): void
    {
        Post::factory()->create(['title' => 'First post']);
        Post::factory()->create(['title' => 'Second post']);
        User::factory()->create(['name' => 'First user']);
        User::factory()->create(['name' => 'Second user']);

        $response = $this
            ->withoutExceptionHandling()
            ->getJson(Restify::path('search', [
                'search' => 'Second',
            ]));

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('users', $response->json('data.1.repositoryName'));
        $this->assertEquals('Second post', $response->json('data.0.title'));
    }

    public function test_global_search_filter_out_unauthorized_repositories()
    {
        Gate::policy(Post::class, PostPolicy::class);

        $_SERVER['restify.post.allowRestify'] = false;

        Post::factory()->create();
        User::factory()->create();

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('search?search=1');

        $this->assertCount(1, $response->json('data'));

        $_SERVER['restify.post.allowRestify'] = null;
    }

    public function test_global_search_filter_will_filter_with_index_query(): void
    {
        $_SERVER['restify.post.indexQueryCallback'] = function ($query) {
            $query->where('id', 2);
        };

        Post::factory()->create(['title' => 'First post']);
        User::factory()->create(['name' => 'First user']);

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('search?search=1');

        $this->assertCount(1, $response->json('data'));

        $_SERVER['restify.post.indexQueryCallback'] = null;
    }
}
