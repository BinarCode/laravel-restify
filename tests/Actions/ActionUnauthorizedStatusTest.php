<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;

class ActionUnauthorizedStatusTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function hidden_index_action_is_forbidden_by_default(): void
    {
        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$this->indexAction()->canSee(fn (): bool => false)]);

        $this->postJson($this->indexRoute('unauthorized-status-index-action'), ['repositories' => 'all'])
            ->assertForbidden();
    }

    #[Test]
    public function hidden_show_action_is_forbidden_by_default(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$this->showAction()->canSee(fn (): bool => false)]);

        $this->postJson($this->showRoute($post, 'unauthorized-status-show-action'))
            ->assertForbidden();
    }

    #[Test]
    public function unknown_action_is_not_found(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([$this->indexAction(), $this->showAction()]);

        $this->postJson($this->indexRoute('missing-action'), ['repositories' => 'all'])
            ->assertNotFound();

        $this->postJson($this->showRoute($post, 'missing-action'))
            ->assertNotFound();
    }

    #[Test]
    public function action_not_offered_on_the_endpoint_is_not_found_even_when_hidden(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('actions')->andReturn([
            $this->indexAction()->canSee(fn (): bool => false),
            $this->showAction()->canSee(fn (): bool => false),
        ]);

        $this->postJson($this->indexRoute('unauthorized-status-show-action'), ['repositories' => 'all'])
            ->assertNotFound();

        $this->postJson($this->showRoute($post, 'unauthorized-status-index-action'))
            ->assertNotFound();
    }

    private function indexRoute(string $uriKey): string
    {
        return PostRepository::route('actions', query: ['action' => $uriKey]);
    }

    private function showRoute(Post $post, string $uriKey): string
    {
        return PostRepository::route("{$post->id}/actions", query: ['action' => $uriKey]);
    }

    private function indexAction(): Action
    {
        return (new class extends Action
        {
            public static $uriKey = 'unauthorized-status-index-action';

            public function handle(ActionRequest $request, $models): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->onlyOnIndex();
    }

    private function showAction(): Action
    {
        return (new class extends Action
        {
            public static $uriKey = 'unauthorized-status-show-action';

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->onlyOnShow();
    }
}
