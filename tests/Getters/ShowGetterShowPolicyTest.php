<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ScopedPostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * A show getter reads a single model, so the user must be allowed to show that model.
 */
class ShowGetterShowPolicyTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([ScopedPostRepository::class]);

        $this->authenticate();
    }

    #[Test]
    public function a_show_getter_on_a_model_the_user_may_not_show_is_forbidden(): void
    {
        $shown = Post::factory()->create(['title' => 'Shown']);
        $denied = Post::factory()->create(['title' => 'Withheld title']);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post): bool => $post->isNot($denied);

        $this->getJson(ScopedPostRepository::route($denied))->assertForbidden();

        $this->showGetter($denied)
            ->assertForbidden()
            ->assertJsonPath('message', 'Not authorized to run this getter.')
            ->assertDontSee('Withheld title');

        $this->showGetter($shown)
            ->assertOk()
            ->assertExactJson(['title' => 'Shown']);
    }

    #[Test]
    public function a_model_outside_the_show_query_is_not_found_before_the_policy_is_asked(): void
    {
        $post = Post::factory()->create();

        ScopedPostRepository::$unshowable = [$post->getKey()];

        $asked = false;

        $_SERVER['restify.post.show.callback'] = function () use (&$asked): bool {
            $asked = true;

            return false;
        };

        $this->showGetter($post)->assertNotFound();

        $this->assertFalse($asked);
    }

    #[Test]
    public function an_index_getter_does_not_ask_the_show_policy(): void
    {
        $post = Post::factory()->create();

        $_SERVER['restify.post.show'] = false;

        $this->getJson(ScopedPostRepository::route('getters/reachable-posts', query: ['repositories' => 'all']))
            ->assertOk()
            ->assertJsonPath('filtered', [$post->getKey()]);
    }

    private function showGetter(Post $post): TestResponse
    {
        return $this->getJson(ScopedPostRepository::route("{$post->getKey()}/getters/post-title"));
    }
}
