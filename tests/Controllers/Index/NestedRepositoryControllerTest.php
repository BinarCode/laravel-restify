<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Database\Factories\UserFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;

class NestedRepositoryControllerTest extends IntegrationTestCase
{
    #[Test]
    public function it_can_list_nested(): void
    {
        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        PostFactory::many(5, [
            'user_id' => UserFactory::one()->id,
        ]);

        $this->getJson(UserRepository::route('1/posts'))->assertJsonCount(5, 'data');

        UserRepository::$related = [];

        $this->getJson(UserRepository::route('1/posts'))->assertForbidden();
    }

    #[Test]
    public function it_can_show_nested_using_identifier(): void
    {
        $post = PostFactory::one([
            'title' => 'Post.',
        ]);

        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        $this->getJson(UserRepository::route("$post->user_id/posts/$post->id"))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', 'Post.')
                    ->etc()
            );

        UserRepository::$related = [];

        $this->getJson(UserRepository::route("$post->user_id/posts/$post->id"))->assertForbidden();
    }

    #[Test]
    public function it_can_store_nested_related(): void
    {
        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        $user = UserFactory::one();

        $this->postJson(UserRepository::route("$user->id/posts"), [
            'title' => $title = 'Post.',
        ])
            ->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', $title)
                    ->etc()
            );

        self::assertCount(1, $user->posts()->get());

        UserRepository::$related = [];
        $this->postJson(UserRepository::route("$user->id/posts"), [
            'title' => 'Post.',
        ])->assertForbidden();
    }

    #[Test]
    public function it_can_update_nested_related(): void
    {
        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        $post = PostFactory::one([
            'title' => 'Post',
        ]);

        $this->putJson(UserRepository::route("$post->user_id/posts/$post->id"), [
            'title' => $title = 'Updated.',
        ])
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.attributes.title', $title)
                    ->etc()
            );

        self::assertSame(
            $title,
            $post->fresh()->title
        );

        UserRepository::$related = [];

        $this->putJson(UserRepository::route("$post->user_id/posts/$post->id"), [
            'title' => 'Updated.',
        ])->assertForbidden();
    }

    #[Test]
    public function it_can_delete_nested_related(): void
    {
        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        $post = PostFactory::one([
            'title' => 'Post',
        ]);

        $this->deleteJson(UserRepository::route("$post->user_id/posts/$post->id"))->assertNoContent();

        self::assertNull($post->fresh());

        UserRepository::$related = [];

        $this->deleteJson(UserRepository::route("$post->user_id/posts/$post->id"))->assertForbidden();
    }

    #[Test]
    public function it_will_apply_policies_when_nested_requested(): void
    {
        $_SERVER['restify.post.delete'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
        ];

        $post = PostFactory::one([
            'title' => 'Post',
        ]);

        $this->deleteJson(UserRepository::route("$post->user_id/posts/$post->id"))->assertForbidden();
    }
}
