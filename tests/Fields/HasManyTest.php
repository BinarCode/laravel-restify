<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class HasManyTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            UserWithPosts::class,
        ]);

        unset($_SERVER['restify.post.show']);
        unset($_SERVER['restify.post.delete']);
        unset($_SERVER['restify.post.allowRestify']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_present_on_relations()
    {
        factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]);

        $this->get(UserWithPosts::uriKey())
            ->assertJsonStructure([
                'data' => [
                    [
                        'relationships' => [
                            'posts',
                        ],
                    ],
                ],
            ]);
    }

    public function test_paginated_on_relation()
    {
        tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->id, 20);
        });

        $this->get(UserWithPosts::uriKey().'?relatablePerPage=20')
            ->assertJsonCount(20, 'data.0.relationships.posts');
    }

    public function test_unauthorized_see_relationship_posts()
    {
        $_SERVER['restify.post.show'] = false;

        Gate::policy(Post::class, PostPolicy::class);
        tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->id, 20);
        });

        $this->get(UserWithPosts::uriKey())
            ->assertForbidden();
    }

    public function test_field_ignored_when_storing()
    {
        tap(factory(User::class)->create(), function ($user) {
            $this->postJson(UserWithPosts::uriKey(), [
                'name' => 'Eduard Lupacescu',
                'email' => 'eduard.lupacescu@binarcode.com',
                'password' => 'strong!',
                'posts' => 'wew',
            ])->assertCreated();
        });
    }

    public function test_can_display_other_pages()
    {
        tap($u = $this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->id, 20);
        });

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', 'posts', PostRepository::class),
            ]);

        $this->get(UserWithPosts::uriKey()."/{$u->id}/posts?perPage=5")
            ->assertJsonCount(5, 'data');
    }

    public function test_can_apply_filters()
    {
        tap($u = $this->mockUsers()->first(), function ($user) {
            tap($this->mockPosts($user->id, 20), function (Collection $posts) {
                $first = $posts->first();
                $first->title = 'wew';
                $first->save();
            });
        });

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', 'posts', PostRepository::class),
            ]);

        $this->get(UserWithPosts::uriKey()."/{$u->id}/posts?title=wew")
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_unauthorized_posts()
    {
        $_SERVER['restify.post.show'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        tap($u = $this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->id, 5);
        });

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', 'posts', PostRepository::class),
            ]);

        $this->get(UserWithPosts::uriKey()."/{$u->id}/posts")
            ->assertJsonCount(0, 'data');

        $_SERVER['restify.post.allowRestify'] = false;

        $this->get(UserWithPosts::uriKey()."/{$u->id}/posts")
            ->assertForbidden();
    }

    public function test_can_store()
    {
        $_SERVER['restify.post.store'] = true;

        Gate::policy(Post::class, PostPolicy::class);

        $this->assertDatabaseCount('posts', 0);
        $u = $this->mockUsers()->first();

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', 'posts', PostRepository::class),
            ]);

        $this->post(UserWithPosts::uriKey()."/{$u->id}/posts", [
            'title' => 'Test',
        ])->assertCreated();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_can_show()
    {
        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', 'posts', PostRepository::class),
            ]);

        $this->get(UserWithPosts::uriKey()."/{$userId}/posts/{$post->id}", [
            'title' => 'Test',
        ])->assertJsonStructure([
            'data' => ['attributes'],
        ])->assertOk();
    }

    public function test_unauthorized_show()
    {
        $_SERVER['restify.post.show'] = false;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->get(UserWithPosts::uriKey()."/{$userId}/posts/{$post->id}", [
            'title' => 'Test',
        ])->assertForbidden();
    }

    public function test_404_post_from_different_owner()
    {
        $_SERVER['restify.post.show'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();
        $secondPost = $this->mockPosts($secondUserId = $this->mockUsers()->first()->id, 1)->first();

        $this->get(UserWithPosts::uriKey()."/{$userId}/posts/{$secondPost->id}")
            ->assertNotFound();
    }

    public function test_change_post()
    {
        $_SERVER['restify.post.update'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->post(UserWithPosts::uriKey()."/{$userId}/posts/{$post->id}", [
            'title' => 'Test',
        ])->assertOk();

        $this->assertSame('Test', $post->fresh()->title);
    }

    public function test_delete_post()
    {
        $_SERVER['restify.post.delete'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->assertDatabaseCount('posts', 1);

        $this->delete(UserWithPosts::uriKey()."/{$userId}/posts/{$post->id}", [
            'title' => 'Test',
        ])->assertNoContent();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_unauthorized_delete_post()
    {
        $_SERVER['restify.post.delete'] = false;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->assertDatabaseCount('posts', 1);

        $this->delete(UserWithPosts::uriKey()."/{$userId}/posts/{$post->id}", [
            'title' => 'Test',
        ])->assertForbidden();

        $this->assertDatabaseCount('posts', 1);
    }
}

class UserWithPosts extends Repository
{
    public static $model = User::class;

    public function fields(RestifyRequest $request)
    {
        return [
            field('name'),
            field('email'),
            field('password'),

            HasMany::make('posts', 'posts', PostRepository::class),
        ];
    }
}
