<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class BelongsToFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            PostWithUserRepository::class,
        ]);

        unset($_SERVER['restify.post.store']);
        unset($_SERVER['restify.post.allowRestify']);
        unset($_SERVER['restify.users.show']);
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

        $this->getJson(PostWithUserRepository::uriKey())
            ->assertJsonStructure([
                'data' => [
                    [
                        'relationships' => [
                            'user' => [
                                'attributes',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_unauthorized_see_relationship()
    {
        $_SERVER['restify.users.show'] = false;

        Gate::policy(User::class, UserPolicy::class);

        tap(factory(User::class)->create(), function ($user) {
            factory(Post::class)->create(['user_id' => $user->id]);

            $this->get(PostWithUserRepository::uriKey())
                ->assertForbidden();
        });
    }

    public function test_field_used_when_storing()
    {
        tap(factory(User::class)->create(), function ($user) {
            $this->postJson(PostWithUserRepository::uriKey(), [
                'title' => 'Create post with owner.',
                'user' => $user->id,
            ])->assertCreated();
        });
    }

    public function test_unauthorized_via_callback_models_cannot_be_attached()
    {
        PostWithUserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title'),
                BelongsTo::make('user', 'user', UserRepository::class)
                    ->canAttach(function ($request, $repository, $model) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Repository::class, $repository);
                        $this->assertInstanceOf(User::class, $model);

                        return false;
                    }),
            ]);

        tap(factory(User::class)->create(), function ($user) {
            $this->postJson(PostWithUserRepository::uriKey(), [
                'title' => 'Create post with owner.',
                'user' => $user->id,
            ])->assertForbidden();
        });
    }

    public function test_unauthorized_via_policy_models_cannot_be_attached()
    {
        $_SERVER['restify.post.allowAttachUser'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->assertDatabaseCount('posts', 0);

        tap(factory(User::class)->create(), function ($user) {
            $this->postJson(PostWithUserRepository::uriKey(), [
                'title' => 'Create post with owner.',
                'user' => $user->id,
            ])->assertForbidden();

            $this->assertDatabaseCount('posts', 0);

            $_SERVER['restify.post.allowAttachUser'] = true;

            $this->postJson(PostWithUserRepository::uriKey(), [
                'title' => 'Create post with owner.',
                'user' => $user->id,
            ])->assertCreated();
        });

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_unauthorized_without_authorization_method_defined_to_attach_models()
    {
        Gate::policy(Post::class, PostPolicyWithoutMethod::class);

        tap(factory(User::class)->create(), function ($user) {
            $this->postJson(PostWithUserRepository::uriKey(), [
                'title' => 'Create post with owner.',
                'user' => $user->id,
            ])->assertForbidden();
        });
    }

    public function test_field_used_when_updating()
    {
        tap(factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]), function ($post) {
            $newOwner = factory(User::class)->create();
            $this->put(PostWithUserRepository::uriKey()."/{$post->id}", [
                'title' => 'Can change post owner.',
                'user' => $newOwner->id,
            ])->assertOk();

            $this->assertSame($post->fresh()->user->id, $newOwner->id);
        });
    }

    public function test_unauthorized_via_policy_when_updating()
    {
        $_SERVER['restify.post.allowAttachUser'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        tap(factory(Post::class)->create([
            'user_id' => factory(User::class),
        ]), function ($post) {
            $firstOwnerId = $post->user->id;
            $newOwner = factory(User::class)->create();
            $this->put(PostWithUserRepository::uriKey()."/{$post->id}", [
                'title' => 'Can change post owner.',
                'user' => $newOwner->id,
            ])->assertForbidden();

            $this->assertSame($post->fresh()->user->id, $firstOwnerId);
        });
    }
}

class PostWithUserRepository extends Repository
{
    public static $model = Post::class;

    public function fields(RestifyRequest $request)
    {
        return [
            field('title'),

            BelongsTo::make('user', 'user', UserRepository::class),
        ];
    }
}

class PostPolicyWithoutMethod
{
    public function allowRestify($user)
    {
        return true;
    }

    public function store($user)
    {
        return true;
    }
}
