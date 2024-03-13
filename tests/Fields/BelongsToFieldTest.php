<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

class BelongsToFieldTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        Restify::repositories([
            PostWithUserRepository::class,
        ]);

        unset($_SERVER['restify.post.store'], $_SERVER['restify.post.allowRestify'], $_SERVER['restify.users.show']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_present_on_show_when_specified_related(): void
    {
        $post = PostFactory::one();

        PostRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'user' => BelongsTo::make('user', UserRepository::class),
            ]);

        $this->getJson(PostRepository::route($post, query: [
            'related' => 'user',
        ]))
            ->assertJsonStructure([
                'included' => [
                    [
                        'id',
                        'type',
                        'attributes'
                    ],
                ],
            ]);

        $relationships = $this->getJson(PostRepository::route($post))
            ->json('included');

        $this->assertNull($relationships);
    }

    public function test_unauthorized_see_relationship(): void
    {
        $_SERVER['restify.users.show'] = false;

        Gate::policy(User::class, UserPolicy::class);

        tap(Post::factory()->create([
            'user_id' => User::factory(),
        ]), function ($post) {
            $this->getJson(PostWithUserRepository::route($post, query: [
                'related' => 'user',
            ]))->assertForbidden();
        });
    }

    public function test_dont_show_key_when_nullable_related(): void
    {
        $_SERVER['restify.users.show'] = true;

        Gate::policy(User::class, UserPolicy::class);

        tap(Post::factory()->create([
            'user_id' => null,
        ]), function ($post) {
            $this->getJson(PostWithUserRepository::route($post, query: [
                'related' => 'user',
            ]))
                ->assertJson(fn (AssertableJson $json) => $json
                    ->where('data.relationships.user.data', null)
                    ->etc()
                )
                ->assertOk();
        });
    }

    public function test_field_used_when_storing(): void
    {
        tap(User::factory()->create(), function ($user) {
            $this->postJson(PostWithUserRepository::route(), [
                'data' => [
                    'attributes' => [
                        'title' => 'Create post with owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->getKey(),
                            ],
                        ],
                    ],
                ],
            ])->assertCreated()->assertJson(fn (AssertableJson $json) => $json
                ->where('data.relationships.user.data.id', (string) ($user->getKey()))
                ->etc()
            );
        });
    }

    public function test_unauthorized_via_callback_models_cannot_be_attached(): void
    {
        PostWithUserRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title'),
                BelongsTo::make('user', UserRepository::class)
                    ->canAttach(function ($request, $repository, $model) {
                        $this->assertInstanceOf(RestifyRequest::class, $request);
                        $this->assertInstanceOf(Repository::class, $repository);
                        $this->assertInstanceOf(User::class, $model);

                        return false;
                    }),
            ]);

        tap(User::factory()->create(), function ($user) {
            $this->postJson(PostWithUserRepository::route(), [
                'title' => 'Create post with owner.',
                'user' => $user->getKey(),
            ])->assertForbidden();
        });
    }

    public function test_unauthorized_via_policy_models_cannot_be_attached(): void
    {
        $_SERVER['restify.post.allowAttachUser'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->assertDatabaseCount('posts', 0);

        tap(User::factory()->create(), function ($user) {
            $this->postJson(PostWithUserRepository::route(), [
                'data' => [
                    'attributes' => [
                        'title' => 'Create post with owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->getKey(),
                            ],
                        ],
                    ],
                ],
            ])->assertForbidden();

            $this->assertDatabaseCount('posts', 0);

            $_SERVER['restify.post.allowAttachUser'] = true;

            $this->postJson(PostWithUserRepository::route(), [
                'data' => [
                    'attributes' => [
                        'title' => 'Create post with owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->getKey(),
                            ],
                        ],
                    ],
                ],
            ])->assertCreated();
        });

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_unauthorized_without_authorization_method_defined_to_attach_models(): void
    {
        Gate::policy(Post::class, PostPolicyWithoutMethod::class);

        tap(User::factory()->create(), function ($user) {
            $this->postJson(PostWithUserRepository::route(), [
                'data' => [
                    'attributes' => [
                        'title' => 'Create post with owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $user->getKey(),
                            ],
                        ],
                    ],
                ]
            ])->assertForbidden();
        });
    }

    public function test_field_used_when_updating(): void
    {
        tap(Post::factory()->create([
            'user_id' => User::factory(),
        ]), function ($post) {
            $newOwner = User::factory()->create();
            $this->putJson(PostWithUserRepository::route($post), [
                'data' => [
                    'attributes' => [
                        'title' => 'Can change post owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $newOwner->getKey(),
                            ],
                        ],
                    ],
                ],
            ])->assertOk();

            $this->assertSame($post->fresh()->user->id, $newOwner->id);
        });
    }

    public function test_unauthorized_via_policy_when_updating(): void
    {
        $_SERVER['restify.post.allowAttachUser'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        tap(Post::factory()->create([
            'user_id' => User::factory(),
        ]), function ($post) {
            $firstOwnerId = $post->user->id;
            $newOwner = User::factory()->create();
            $this->putJson(PostWithUserRepository::route($post), [
                'data' => [
                    'attributes' => [
                        'title' => 'Can change post owner.',
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'users',
                                'id' => $newOwner->getKey(),
                            ],
                        ],
                    ],
                ],
            ])->assertForbidden();

            $this->assertSame($post->fresh()->user->id, $firstOwnerId);
        });
    }

    public function test_belongs_to_could_choose_columns(): void
    {
        $post = PostFactory::one();

        PostRepository::partialMock()
            ->shouldReceive('include')
            ->andReturn([
                'user' => BelongsTo::make('user', UserRepository::class),
            ]);

        $this->withoutExceptionHandling();

        $this->getJson(PostRepository::route($post, query: [
            'include' => 'user[name]',
        ]))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('included.0.attributes.name')
                    ->missing('included.0.attributes.email')
                    ->etc()
            );
    }
}

class PostWithUserRepository extends Repository
{
    public static $model = Post::class;

    public static function include(): array
    {
        return [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
        ];
    }

    public static function related(): array {
        return [
            'user' => BelongsTo::make('user', UserRepository::class),
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
