<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;

class GetterUnauthorizedStatusTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function hidden_index_getter_is_forbidden(): void
    {
        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$this->indexGetter()->canSee(fn (): bool => false)]);

        $this->getJson($this->indexRoute('unauthorized-status-index-getter'))
            ->assertForbidden()
            ->assertJsonPath('message', 'You don\'t have permission to run this getter.');
    }

    #[Test]
    public function hidden_show_getter_is_forbidden(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$this->showGetter()->canSee(fn (): bool => false)]);

        $this->getJson($this->showRoute($post, 'unauthorized-status-show-getter'))
            ->assertForbidden()
            ->assertJsonPath('message', 'You don\'t have permission to run this getter.');
    }

    #[Test]
    public function unknown_getter_is_not_found(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([$this->indexGetter(), $this->showGetter()]);

        $this->getJson($this->indexRoute('missing-getter'))
            ->assertNotFound()
            ->assertJsonPath('message', 'Getter not found.');

        $this->getJson($this->showRoute($post, 'missing-getter'))
            ->assertNotFound()
            ->assertJsonPath('message', 'Getter not found.');
    }

    #[Test]
    public function getter_not_offered_on_the_endpoint_is_not_found_even_when_hidden(): void
    {
        $post = $this->mockPost();

        PostRepository::partialMock()->shouldReceive('getters')->andReturn([
            $this->indexGetter()->canSee(fn (): bool => false),
            $this->showGetter()->canSee(fn (): bool => false),
        ]);

        $this->getJson($this->indexRoute('unauthorized-status-show-getter'))
            ->assertNotFound();

        $this->getJson($this->showRoute($post, 'unauthorized-status-index-getter'))
            ->assertNotFound();
    }

    private function indexRoute(string $uriKey): string
    {
        return PostRepository::route("getters/{$uriKey}");
    }

    private function showRoute(Post $post, string $uriKey): string
    {
        return PostRepository::route("{$post->id}/getters/{$uriKey}");
    }

    private function indexGetter(): Getter
    {
        return (new class extends Getter
        {
            public static $uriKey = 'unauthorized-status-index-getter';

            public function handle(GetterRequest $request): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->onlyOnIndex();
    }

    private function showGetter(): Getter
    {
        return (new class extends Getter
        {
            public static $uriKey = 'unauthorized-status-show-getter';

            public function handle(GetterRequest $request, ?Post $post = null): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->onlyOnShow();
    }
}
