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
use Illuminate\Testing\Fluent\AssertableJson;

class HasManyTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            UserWithPosts::class,
        ]);

        unset($_SERVER['restify.post.show'], $_SERVER['restify.post.delete'], $_SERVER['restify.post.allowRestify']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_has_many_present_on_relations(): void
    {
        $user = User::factory()->create();

        Post::factory()->times(2)->create([
            'user_id' => $user->getKey(),
        ]);

        $this->getJson(UserWithPosts::route($user->getKey(), [
            'related' => 'posts',
        ]))->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'posts' => [
                        [
                            'id',
                            'attributes',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_has_many_could_choose_columns(): void
    {
        $user = User::factory()->create();

        Post::factory()->times(2)->create([
            'title' => 'Title',
            'description' => 'Description',
            'user_id' => $user->getKey(),
        ]);

        $this->getJson(UserWithPosts::route($user->getKey(), ['related' => 'posts[title]']))
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('data.relationships.posts.0.attributes.title', 'Title')
                    ->etc()
            );

        $this->getJson(UserWithPosts::route($user->getKey(), ['related' => 'posts[title|description]']))
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('data.relationships.posts.0.attributes.title', 'Title')
                    ->where('data.relationships.posts.0.attributes.description', 'Description')
                    ->etc()
            );
    }

    public function test_has_many_paginated_on_relation(): void
    {
        $user = tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 22);
        });

        $this->getJson(UserWithPosts::route($user->getKey(), ['related' => 'posts', 'relatablePerPage' => 20]))
            ->assertJsonCount(20, 'data.relationships.posts');
    }

    public function test_has_many_filter_unauthorized_to_see_relationship_posts(): void
    {
        $_SERVER['restify.post.show'] = false;

        Gate::policy(Post::class, PostPolicy::class);
        $user = tap($this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 20);
        });

        $this->getJson(UserWithPosts::route($user->getKey(), ['related' => 'posts']))
            ->assertOk()
            ->assertJson(fn(AssertableJson $json) => $json->count('data.relationships.posts', 0)->etc());
    }

    public function test_field_ignored_when_storing(): void
    {
        tap(User::factory()->create(), function ($user) {
            $this->postJson(UserWithPosts::route(), [
                'name' => 'Eduard Lupacescu',
                'email' => 'eduard.lupacescu@binarcode.com',
                'password' => 'strong!',
                'posts' => 'wew',
            ])->assertCreated();
        });
    }

    public function test_can_display_other_pages(): void
    {
        tap($u = $this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 20);
        });

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', PostRepository::class),
            ]);

        $this->getJson(UserWithPosts::route("$u->id/posts", ['perPage' => 5]))
            ->assertJsonCount(5, 'data');
    }

    public function test_can_apply_filters(): void
    {
        tap($u = $this->mockUsers()->first(), function ($user) {
            tap($this->mockPosts($user->getKey(), 20), static function (Collection $posts) {
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

                HasMany::make('posts', PostRepository::class),
            ]);

        $this->getJson(UserWithPosts::route("$u->id/posts", ['title' => 'wew']))
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_unauthorized_posts(): void
    {
        $_SERVER['restify.post.show'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        tap($u = $this->mockUsers()->first(), function ($user) {
            $this->mockPosts($user->getKey(), 5);
        });

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', PostRepository::class),
            ]);

        $this->getJson(UserWithPosts::route("$u->id/posts"))
            ->assertJsonCount(0, 'data');

        $_SERVER['restify.post.allowRestify'] = false;

        $this->getJson(UserWithPosts::route("$u->id/posts"))
            ->assertForbidden();
    }

    public function test_can_store(): void
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

                HasMany::make('posts', PostRepository::class),
            ]);

        $this->postJson(UserWithPosts::route("$u->id/posts"), [
            'title' => 'Test',
        ])->assertCreated();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_can_show(): void
    {
        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        UserWithPosts::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('name'),
                field('email'),
                field('password'),

                HasMany::make('posts', PostRepository::class),
            ]);

        $this->getJson(UserWithPosts::route("$userId/posts/$post->id"), [
            'title' => 'Test',
        ])->assertJsonStructure([
            'data' => ['attributes'],
        ])->assertOk();
    }

    public function test_unauthorized_show(): void
    {
        $_SERVER['restify.post.show'] = false;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->getJson(UserWithPosts::route("{$userId}/posts/{$post->id}"), [
            'title' => 'Test',
        ])->assertForbidden();
    }

    public function test_404_post_from_different_owner(): void
    {
        $_SERVER['restify.post.show'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();
        $secondPost = $this->mockPosts($secondUserId = $this->mockUsers()->first()->id, 1)->first();

        $this->getJson(UserWithPosts::route("/{$userId}/posts/{$secondPost->id}"))
            ->assertNotFound();
    }

    public function test_change_post(): void
    {
        $_SERVER['restify.post.update'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->postJson(UserWithPosts::route("/{$userId}/posts/{$post->id}"), [
            'title' => 'Test',
        ])->assertOk();

        $this->assertSame('Test', $post->fresh()->title);
    }

    public function test_delete_post(): void
    {
        $_SERVER['restify.post.delete'] = true;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->assertDatabaseCount('posts', 1);

        $this->deleteJson(UserWithPosts::route("/{$userId}/posts/{$post->id}"), [
            'title' => 'Test',
        ])->assertNoContent();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_unauthorized_delete_post(): void
    {
        $_SERVER['restify.post.delete'] = false;
        Gate::policy(Post::class, PostPolicy::class);

        $post = $this->mockPosts($userId = $this->mockUsers()->first()->id, 1)->first();

        $this->assertDatabaseCount('posts', 1);

        $this->deleteJson(UserWithPosts::route("/{$userId}/posts/{$post->id}"), [
            'title' => 'Test',
        ])->assertForbidden();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_it_validates_fields_when_storing_related(): void
    {
        $userId = $this->mockUsers()->first()->id;
        $this->postJson(UserWithPosts::route("/{$userId}/posts"), [
            /*'title' => 'Wew',*/
        ])->assertStatus(422);
    }
}

class UserWithPosts extends Repository
{
    public static $model = User::class;

    public static function include(): array
    {
        return [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
            field('password'),
        ];
    }
}
