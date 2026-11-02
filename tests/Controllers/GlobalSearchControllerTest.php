<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

class GlobalSearchControllerTest extends IntegrationTestCase
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
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
        );
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
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->etc()
        );
    }

    public function test_global_search_skips_models_the_user_cannot_show(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $hidden = Post::factory()->create(['title' => 'Hidden post']);
        $visible = Post::factory()->create(['title' => 'Visible post']);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post) => $post->isNot($hidden);

        $response = $this->getJson(Restify::path('search', [
            'search' => 'post',
        ]))->assertOk();

        $this->assertSame([$visible->getKey()], array_column($response->json('data'), 'repositoryId'));
    }
}
