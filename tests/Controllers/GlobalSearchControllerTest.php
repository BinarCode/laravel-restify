<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class GlobalSearchControllerTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_global_search_returns_matches()
    {
        factory(Post::class)->create(['title' => 'First post']);
        factory(Post::class)->create(['title' => 'Second post']);
        factory(User::class)->create(['name' => 'First user']);
        factory(User::class)->create(['name' => 'Second user']);


        $response = $this
            ->withoutExceptionHandling()
            ->getJson('/restify-api/search?search=Second');

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals('users', $response->json('data.1.repositoryName'));
        $this->assertEquals('Second post', $response->json('data.0.title'));
    }

    public function test_global_search_filter_out_unauthorized_repositories()
    {
        Gate::policy(Post::class, PostPolicy::class);

        $_SERVER['restify.post.allowRestify'] = false;

        factory(Post::class)->create();
        factory(User::class)->create();


        $response = $this
            ->withoutExceptionHandling()
            ->getJson('/restify-api/search?search=1');

        $this->assertCount(1, $response->json('data'));

        $_SERVER['restify.post.allowRestify'] = null;
    }

    public function test_global_search_filter_will_filter_with_index_query()
    {
        $_SERVER['restify.post.indexQueryCallback'] = function ($query) {
            $query->where('id', 2);
        };

        factory(Post::class)->create(['title' => 'First post']);
        factory(User::class)->create(['name' => 'First user']);

        $response = $this
            ->withoutExceptionHandling()
            ->getJson('/restify-api/search?search=1');

        $this->assertCount(1, $response->json('data'));

        $_SERVER['restify.post.indexQueryCallback'] = null;
    }
}
