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

        $getter = $this->showGetter()->canRun(fn () => false);

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$getter]);

        $this
            ->getJson($this->getterUrl($getter, $post->id))
            ->assertForbidden();
    }

    #[Test]
    public function show_getter_allowed_by_can_run_executes_and_receives_the_request_and_model(): void
    {
        $post = $this->mockPost();

        $receivedRequest = null;
        $receivedModel = null;

        $getter = $this->showGetter()->canRun(function ($request, $model) use (&$receivedRequest, &$receivedModel) {
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

    private function getterUrl(Getter $getter, int|string $modelId): string
    {
        return PostRepository::route("{$modelId}/getters/{$getter->uriKey()}");
    }
}
