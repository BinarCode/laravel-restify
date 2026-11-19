<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\McpScopedPostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The MCP show action and show getter tools need the show policy of their model, as over REST.
 */
class McpShowPolicyForActionsAndGettersTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'test-show-policy-actions-and-getters';

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpScopedPostRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function a_show_action_tool_does_not_run_on_a_model_the_user_may_not_show(): void
    {
        $shown = Post::factory()->create(['is_active' => false]);
        $denied = Post::factory()->create(['is_active' => false]);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post): bool => $post->isNot($denied);

        $result = $this->callTool('mcp-scoped-posts-activate-post-action-tool', ['id' => (string) $denied->getKey()]);

        $this->assertSame('Not authorized to run this action.', $result['error'] ?? null);
        $this->assertArrayNotHasKey('success', $result);
        $this->assertDatabaseHas(Post::class, ['id' => $denied->getKey(), 'is_active' => false]);

        $result = $this->callTool('mcp-scoped-posts-activate-post-action-tool', ['id' => (string) $shown->getKey()]);

        $this->assertTrue($result['success'] ?? false);
        $this->assertDatabaseHas(Post::class, ['id' => $shown->getKey(), 'is_active' => true]);
    }

    #[Test]
    public function a_show_getter_tool_does_not_read_a_model_the_user_may_not_show(): void
    {
        $shown = Post::factory()->create(['title' => 'Shown']);
        $denied = Post::factory()->create(['title' => 'Withheld title']);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post): bool => $post->isNot($denied);

        $result = $this->callTool('mcp-scoped-posts-post-title-getter-tool', ['id' => (string) $denied->getKey()]);

        $this->assertSame('Not authorized to run this getter.', $result['error'] ?? null);
        $this->assertArrayNotHasKey('success', $result);
        $this->assertStringNotContainsString('Withheld title', (string) json_encode($result));

        $result = $this->callTool('mcp-scoped-posts-post-title-getter-tool', ['id' => (string) $shown->getKey()]);

        $this->assertTrue($result['success'] ?? false);
        $this->assertSame('Shown', $result['result']['title']);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callTool(string $name, array $arguments): array
    {
        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ]);

        $text = $response->json('result.content.0.text');

        $decoded = is_string($text) ? json_decode($text, true) : null;

        return is_array($decoded) ? $decoded : (array) $response->json();
    }
}
