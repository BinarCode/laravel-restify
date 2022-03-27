<?php

namespace Binaryk\LaravelRestify\Tests\Fields;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\FieldCollection;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;

class HasOneFieldTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();

        unset($_SERVER['restify.post.show']);

        Restify::repositories([
            UserWithPostRepository::class,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Repository::clearResolvedInstances();
    }

    public function test_has_one_present_on_relations(): void
    {
        $post = Post::factory()->create([
            'user_id' => User::factory(),
        ]);

        $this->getJson(UserWithPostRepository::uriKey()."/$post->id?related=post")
            ->assertJsonStructure([
                'data' => [
                    'relationships' => [
                        'post',
                    ],
                ],
            ]);
    }

    public function test_has_one_field_unauthorized_see_relationship(): void
    {
        $_SERVER['restify.post.show'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        tap(Post::factory()->create([
            'user_id' => User::factory(),
        ]), function (Post $post) {
            $this->postJson(UserWithPostRepository::to($post->id, [
                'include' => 'post',
            ]))->dd();
        });
    }

    public function test_field_ignored_when_storing()
    {
        UserWithPostRepository::partialMock()
            ->shouldReceive('fillFields')
            ->withArgs(function ($request, $model, FieldCollection $fields) {
                $this->assertFalse(
                    $fields->some('attribute', 'post')
                );
            });

        $this->postJson(UserWithPostRepository::uriKey(), [
            'name' => 'Eduard Lupacescu',
            'email' => 'eduard.lupacescu@binarcode.com',
            'password' => 'strong!',
            'post' => 'wew',
        ])->assertCreated();
    }

    public function test_can_sort_using_has_one_to_field(): void
    {
        UserRepository::$related = [
            'post' => HasOne::make('post', PostRepository::class)->sortable('posts.title'),
        ];

        Post::factory()->create([
            'title' => 'Zez',
            'user_id' => User::factory()->create([
                'name' => 'Last',
            ]),
        ]);

        Post::factory()->create([
            'title' => 'Abc',
            'user_id' => User::factory()->create([
                'name' => 'First',
            ]),
        ]);

        $this
            ->getJson(UserRepository::uriKey().'?related=post&sort=-post.attributes.title&perPage=5')
            ->assertJson(function (AssertableJson $assertableJson) {
                $assertableJson
                    ->where('data.1.attributes.name', 'First')
                    ->where('data.0.attributes.name', 'Last')
                    ->etc();
            });

        $this
            ->getJson(UserRepository::uriKey().'?related=post&sort=post.attributes.title&perPage=5')
            ->assertJson(function (AssertableJson $assertableJson) {
                $assertableJson
                    ->where('data.0.attributes.name', 'First')
                    ->where('data.1.attributes.name', 'Last')
                    ->etc();
            });
    }
}

class UserWithPostRepository extends Repository
{
    public static $model = User::class;

    public static function related(): array
    {
        return [
            'post' => HasOne::make('post', PostRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::new('name'),
            Field::new('email'),
            Field::new('password'),
        ];
    }

    public static function uriKey(): string
    {
        return 'user-with-post-repository';
    }
}
