<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ScopedPostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

/**
 * A show action runs on a single model, so the user must be allowed to show that model.
 */
class ShowActionShowPolicyTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([ScopedPostRepository::class]);

        $this->authenticate();
    }

    #[Test]
    public function a_show_action_on_a_model_the_user_may_not_show_is_forbidden(): void
    {
        $shown = Post::factory()->create(['is_active' => false]);
        $denied = Post::factory()->create(['is_active' => false]);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post): bool => $post->isNot($denied);

        $this->getJson(ScopedPostRepository::route($denied))->assertForbidden();

        $this->showAction($denied)
            ->assertForbidden()
            ->assertJsonPath('message', 'Not authorized to run this action.');

        $this->assertDatabaseHas(Post::class, ['id' => $denied->getKey(), 'is_active' => false]);

        $this->showAction($shown)
            ->assertOk()
            ->assertExactJson(['activated' => [$shown->getKey()]]);

        $this->assertDatabaseHas(Post::class, ['id' => $shown->getKey(), 'is_active' => true]);
    }

    #[Test]
    public function a_model_outside_the_show_query_is_not_found_before_the_policy_is_asked(): void
    {
        $post = Post::factory()->create(['is_active' => false]);

        ScopedPostRepository::$unshowable = [$post->getKey()];

        $asked = false;

        $_SERVER['restify.post.show.callback'] = function () use (&$asked): bool {
            $asked = true;

            return false;
        };

        $this->showAction($post)->assertNotFound();

        $this->assertFalse($asked);
    }

    #[Test]
    public function can_run_is_not_asked_about_a_model_the_user_may_not_show(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $asked = false;

        $action = (new class extends Action
        {
            public static $uriKey = 'activate-post-with-can-run';

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                $post->update(['is_active' => true]);

                return response()->json(['ok' => true]);
            }
        })->onlyOnShow()->canRun(function (Request $request, ?Post $model) use (&$asked): bool {
            $asked = true;

            return true;
        });

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $_SERVER['restify.post.show'] = false;

        $this->postJson(PostRepository::route((string) $post->getKey(), action: $action))->assertForbidden();

        $this->assertFalse($asked);
        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'is_active' => false]);

        $_SERVER['restify.post.show'] = true;

        $this->postJson(PostRepository::route((string) $post->getKey(), action: $action))->assertOk();

        $this->assertTrue($asked);
        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'is_active' => true]);
    }

    #[Test]
    public function the_show_policy_check_of_show_actions_can_be_turned_off(): void
    {
        config()->set('restify.repositories.authorize_show_for_actions_and_getters', false);

        $post = $this->mockPost(['is_active' => false]);

        $action = (new class extends Action
        {
            public static $uriKey = 'activate-post-with-own-denial';

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                $post->update(['is_active' => true]);

                return response()->json(['ok' => true]);
            }
        })->onlyOnShow()->canRun(fn (Request $request, ?Post $model): bool => abort(422, 'Publish the post first.'));

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $_SERVER['restify.post.show'] = false;

        $this->postJson(PostRepository::route((string) $post->getKey(), action: $action))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Publish the post first.');

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'is_active' => false]);
    }

    private function showAction(Post $post): TestResponse
    {
        return $this->postJson(ScopedPostRepository::route("{$post->getKey()}/actions", query: ['action' => 'activate-post']));
    }
}
