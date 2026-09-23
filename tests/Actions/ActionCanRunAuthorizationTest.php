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

    private static int $defaultChunkCount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();

        self::$defaultChunkCount = Action::$chunkCount;
    }

    protected function tearDown(): void
    {
        Action::$chunkCount = self::$defaultChunkCount;

        parent::tearDown();
    }

    #[Test]
    public function show_action_denied_by_can_run_is_forbidden_over_rest(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $action = $this->showAction()->canRun(fn (Request $request, ?Post $model): bool => false);

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

        $action = $this->showAction()->canRun(function (Request $request, ?Post $model) use (&$receivedRequest, &$receivedModel): bool {
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
    public function denied_can_run_wins_over_action_validation(): void
    {
        $post = $this->mockPost(['is_active' => false]);

        $handleFlag = new \stdClass;
        $handleFlag->called = false;

        $action = $this->validatingShowAction($handleFlag)->canRun(fn (Request $request, ?Post $model): bool => false);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route((string) $post->id, action: $action), [
                // Deliberately omits 'title', which the action's own rules() require.
            ])
            ->assertForbidden();

        $this->assertFalse($handleFlag->called);

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function standalone_action_denied_by_can_run_is_forbidden_over_rest(): void
    {
        $action = $this->standaloneAction()->canRun(fn (Request $request, ?Post $model): bool => false);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]))
            ->assertForbidden();
    }

    #[Test]
    public function standalone_action_allowed_by_can_run_executes_over_rest(): void
    {
        $action = $this->standaloneAction()->canRun(fn (Request $request, ?Post $model): bool => true);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    #[Test]
    public function index_action_invokes_can_run_for_each_model_in_the_batch(): void
    {
        $posts = Post::factory()->count(3)->create(['is_active' => false]);

        /** @var Collection<int, int> $seenIds */
        $seenIds = Collection::make();

        $action = $this->bulkAction()->canRun(function (Request $request, ?Post $model) use (&$seenIds): bool {
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

        $action = $this->bulkAction()->canRun(fn (Request $request, ?Post $model): bool => $model->id !== $deniedId);

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
    public function index_action_with_string_ids_and_a_denied_row_fails_without_side_effects(): void
    {
        $posts = Post::factory()->count(3)->create(['is_active' => false]);
        $deniedId = (string) $posts->first()->id;

        $action = $this->bulkAction()->canRun(fn (Request $request, ?Post $model): bool => (string) $model->id !== $deniedId);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]), [
                'repositories' => $posts->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
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
    public function index_action_with_all_repositories_denied_in_the_last_chunk_rolls_back_earlier_chunks(): void
    {
        Action::$chunkCount = 2;

        $posts = Post::factory()->count(4)->create(['is_active' => false]);
        $deniedId = $posts->sortBy('id')->first()->id;

        $action = $this->bulkAction()->canRun(fn (Request $request, ?Post $model): bool => $model->id !== $deniedId);

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$action]);

        $this
            ->postJson(PostRepository::route('actions', query: ['action' => $action->uriKey()]), [
                'repositories' => 'all',
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
    public function index_action_with_explicit_id_list_denied_in_the_last_chunk_rolls_back_earlier_chunks(): void
    {
        Action::$chunkCount = 2;

        $posts = Post::factory()->count(4)->create(['is_active' => false]);
        $deniedId = $posts->sortBy('id')->first()->id;

        $action = $this->bulkAction()->canRun(fn (Request $request, ?Post $model): bool => $model->id !== $deniedId);

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

        $withCanRun = $this->bulkAction()->canRun(fn (Request $request, ?Post $model): bool => true);

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

    private function validatingShowAction(\stdClass $handleFlag): Action
    {
        return (new class($handleFlag) extends Action
        {
            public static $uriKey = 'can-run-validating-show-action';

            public function __construct(private readonly \stdClass $handleFlag) {}

            public function rules(): array
            {
                return ['title' => ['required', 'string']];
            }

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                $this->handleFlag->called = true;

                $request->validate($this->rules());

                $post->update(['is_active' => true]);

                return response()->json(['ok' => true]);
            }
        })->onlyOnShow();
    }

    private function standaloneAction(): Action
    {
        return (new class extends Action
        {
            public static $uriKey = 'can-run-standalone-action';

            public function handle(ActionRequest $request): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->standalone();
    }

    private function bulkAction(): Action
    {
        return new class extends Action
        {
            public static $uriKey = 'can-run-bulk-action';

            public function handle(ActionRequest $request, Collection $models): JsonResponse
            {
                foreach ($models as $post) {
                    $post->update(['is_active' => true]);
                }

                return response()->json(['ok' => true]);
            }
        };
    }
}
