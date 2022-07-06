<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

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

    public function test_global_search_filter_out_unauthorized_repositories(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $_SERVER['restify.post.allowRestify'] = false;

        $this->mockUsers();
        $this->mockPosts();

        $this->getJson(Restify::path('search', [
            'search' => 1,
        ]))->assertJson(fn(AssertableJson $json) => $json
            ->count('data', 1)
            ->etc()
        );

        $_SERVER['restify.post.allowRestify'] = true;
    }

    public function test_global_search_filter_will_filter_with_index_query(): void
    {
        $_SERVER['restify.post.indexQueryCallback'] = function ($query) {
            $query->where('id', 2);
        };

        Post::factory()->create(['title' => 'First post']);
        User::factory()->create(['name' => 'First user']);

        $this->getJson(Restify::path('search', [
            'search' => 1,
        ]))->assertJson(fn(AssertableJson $json) => $json
            ->count('data', 1)
            ->etc()
        );

        $_SERVER['restify.post.indexQueryCallback'] = null;
    }
}
