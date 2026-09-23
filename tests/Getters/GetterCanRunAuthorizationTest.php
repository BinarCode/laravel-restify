<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;

class GetterCanRunAuthorizationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function show_getter_denied_by_can_run_is_forbidden_over_rest(): void
    {
        $post = $this->mockPost();

        $getter = $this->showGetter()->canRun(fn (Request $request, ?Post $model): bool => false);

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->getterUrl($getter, $post->id))
            ->assertForbidden();
    }

    #[Test]
    public function show_getter_denied_by_can_run_never_calls_handle(): void
    {
        $post = $this->mockPost();

        $handleFlag = new \stdClass;
        $handleFlag->called = false;

        $getter = $this->trackingShowGetter($handleFlag)->canRun(fn (Request $request, ?Post $model): bool => false);

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->getterUrl($getter, $post->id))
            ->assertForbidden();

        $this->assertFalse($handleFlag->called);
    }

    #[Test]
    public function show_getter_allowed_by_can_run_executes_and_receives_the_request_and_model(): void
    {
        $post = $this->mockPost();

        $receivedRequest = null;
        $receivedModel = null;

        $getter = $this->showGetter()->canRun(function (Request $request, ?Post $model) use (&$receivedRequest, &$receivedModel): bool {
            $receivedRequest = $request;
            $receivedModel = $model;

            return true;
        });

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->getterUrl($getter, $post->id))
            ->assertOk()
            ->assertJson(['message' => 'show works']);

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertInstanceOf(Post::class, $receivedModel);
        $this->assertSame($post->id, $receivedModel->id);
    }

    #[Test]
    public function getter_without_can_run_is_unaffected(): void
    {
        $post = $this->mockPost();

        $getter = $this->showGetter();

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->getterUrl($getter, $post->id))
            ->assertOk()
            ->assertJson(['message' => 'show works']);
    }

    #[Test]
    public function index_getter_denied_by_can_run_is_forbidden_over_rest(): void
    {
        $getter = $this->indexGetter()->canRun(fn (Request $request, ?Post $model): bool => false);

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->indexGetterUrl($getter))
            ->assertForbidden();
    }

    #[Test]
    public function index_getter_allowed_by_can_run_executes_and_receives_a_null_model(): void
    {
        $receivedRequest = null;
        $receivedModel = 'not-set';

        $getter = $this->indexGetter()->canRun(function (Request $request, ?Post $model) use (&$receivedRequest, &$receivedModel): bool {
            $receivedRequest = $request;
            $receivedModel = $model;

            return true;
        });

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->indexGetterUrl($getter))
            ->assertOk()
            ->assertJson(['message' => 'index works']);

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertNull($receivedModel);
    }

    #[Test]
    public function index_getter_without_can_run_is_unaffected(): void
    {
        $getter = $this->indexGetter();

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->indexGetterUrl($getter))
            ->assertOk()
            ->assertJson(['message' => 'index works']);
    }

    private function showGetter(): Getter
    {
        return new class extends Getter
        {
            public static $uriKey = 'can-run-show-getter';

            public function handle(GetterRequest $request, ?Post $post = null): JsonResponse
            {
                return response()->json(['message' => 'show works']);
            }
        };
    }

    private function trackingShowGetter(\stdClass $handleFlag): Getter
    {
        return new class($handleFlag) extends Getter
        {
            public static $uriKey = 'can-run-tracking-show-getter';

            public function __construct(private readonly \stdClass $handleFlag) {}

            public function handle(GetterRequest $request, ?Post $post = null): JsonResponse
            {
                $this->handleFlag->called = true;

                return response()->json(['message' => 'show works']);
            }
        };
    }

    private function indexGetter(): Getter
    {
        return new class extends Getter
        {
            public static $uriKey = 'can-run-index-getter';

            public function handle(GetterRequest $request): JsonResponse
            {
                return response()->json(['message' => 'index works']);
            }
        };
    }

    private function getterUrl(Getter $getter, int|string $modelId): string
    {
        return PostRepository::route("{$modelId}/getters/{$getter->uriKey()}");
    }

    private function indexGetterUrl(Getter $getter): string
    {
        return PostRepository::route("getters/{$getter->uriKey()}");
    }
}
