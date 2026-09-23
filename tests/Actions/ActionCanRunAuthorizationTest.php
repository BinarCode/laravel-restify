<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class ActionCanRunAuthorizationTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function show_action_denied_by_can_run_is_forbidden_over_rest(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $action = $this->showAction()->canRun(fn () => false);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route((string) $post->id, action: $action))
            ->assertForbidden();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function show_action_allowed_by_can_run_executes_and_receives_the_request_and_model(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $receivedRequest = null;
        $receivedModel = null;

        $action = $this->showAction()->canRun(function ($request, $model) use (&$receivedRequest, &$receivedModel) {
            $receivedRequest = $request;
            $receivedModel = $model;

            return true;
        });

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route((string) $post->id, action: $action))
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertInstanceOf(Post::class, $receivedModel);
        $this->assertSame($post->id, $receivedModel->id);
    }

    #[Test]
    public function action_without_can_run_is_unaffected(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $action = $this->showAction();

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route((string) $post->id, action: $action))
            ->assertOk();

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function index_action_invokes_can_run_for_each_model_in_the_batch(): void
    {
        $posts = Post::factory()->count(3)->create(['is_active' => false]);

        $seenIds = Collection::make();

        $action = $this->bulkAction()->canRun(function ($request, $model) use (&$seenIds) {
            $seenIds->push($model->id);

            return true;
        });

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]), [
                'repositories' => $posts->pluck('id')->all(),
            ])
            ->assertOk();

        $this->assertEqualsCanonicalizing($posts->pluck('id')->all(), $seenIds->all());

        foreach ($posts as $post) {
            $this->assertDatabaseHas(Post::class, [
                'id' => $post->id,
                'is_active' => true,
            ]);
        }
    }

    #[Test]
    public function index_action_with_one_denied_row_fails_the_whole_request_without_side_effects(): void
    {
        $posts = Post::factory()->count(3)->create(['is_active' => false]);
        $deniedId = $posts->first()->id;

        $action = $this->bulkAction()->canRun(fn ($request, $model) => $model->id !== $deniedId);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]), [
                'repositories' => $posts->pluck('id')->all(),
            ])
            ->assertForbidden();

        foreach ($posts as $post) {
            $this->assertDatabaseHas(Post::class, [
                'id' => $post->id,
                'is_active' => false,
            ]);
        }
    }

    #[Test]
    public function checking_can_run_per_row_does_not_add_extra_queries(): void
    {
        $posts = $this->mockPosts(null, 3);

        $withoutCanRun = $this->bulkAction();

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$withoutCanRun]);

        $this->recordQueries();

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $withoutCanRun->uriKey()]), [
                'repositories' => $posts->pluck('id')->all(),
            ])
            ->assertOk();

        $baselineSelects = count($this->selectsAgainst(Post::class));

        $posts = $this->mockPosts(null, 3);

        $withCanRun = $this->bulkAction()->canRun(fn () => true);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$withCanRun]);

        $this->recordQueries();

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $withCanRun->uriKey()]), [
                'repositories' => $posts->pluck('id')->all(),
            ])
            ->assertOk();

        $this->assertSame($baselineSelects, count($this->selectsAgainst(Post::class)));
    }

    private function showAction(): Action
    {
        return (new class extends Action
        {
            public static $uriKey = 'can-run-show-action';

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                $post->update(['is_active' => true]);

                return response()->json(['ok' => true]);
            }
        })->onlyOnShow();
    }

    private function bulkAction(): Action
    {
        return new class extends Action
        {
            public static $uriKey = 'can-run-bulk-action';

            public function handle(ActionRequest $request, Collection $models): JsonResponse
            {
                $models->each(fn (Post $post) => $post->update(['is_active' => true]));

                return response()->json(['ok' => true]);
            }
        };
    }
}
