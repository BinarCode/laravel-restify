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
use PHPUnit\Framework\Attributes\TestWith;

/**
 * The MCP action and getter tools reach the same rows as the REST actions and getters.
 */
class McpActionsGettersQueryScopeTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'test-scoped-actions-and-getters';

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
    #[TestWith(['all'], 'all keyword')]
    #[TestWith(['explicit ids'], 'explicit id list')]
    public function an_index_action_tool_skips_the_rows_the_index_query_excludes(string $repositoriesMode): void
    {
        $listed = Post::factory()->create(['is_active' => false]);
        $unlisted = Post::factory()->create(['is_active' => false]);

        McpScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $result = $this->callTool('mcp-scoped-posts-activate-posts-action-tool', [
            'repositories' => $repositoriesMode === 'all'
                ? '"all"'
                : '['.$listed->getKey().','.$unlisted->getKey().']',
        ]);

        $this->assertTrue($result['success'] ?? false);
        $this->assertSame([$listed->getKey()], $result['result']['activated']);

        $this->assertDatabaseHas(Post::class, ['id' => $listed->getKey(), 'is_active' => true]);
        $this->assertDatabaseHas(Post::class, ['id' => $unlisted->getKey(), 'is_active' => false]);
    }

    #[Test]
    public function a_show_action_tool_does_not_run_on_a_model_the_show_query_excludes(): void
    {
        $shown = Post::factory()->create(['is_active' => false]);
        $notShown = Post::factory()->create(['is_active' => false]);

        McpScopedPostRepository::$unshowable = [$notShown->getKey()];

        $denied = $this->callTool('mcp-scoped-posts-activate-post-action-tool', ['id' => (string) $notShown->getKey()]);

        $this->assertArrayNotHasKey('success', $denied);
        $this->assertTrue($denied['result']['isError'] ?? false);
        $this->assertDatabaseHas(Post::class, ['id' => $notShown->getKey(), 'is_active' => false]);

        $allowed = $this->callTool('mcp-scoped-posts-activate-post-action-tool', ['id' => (string) $shown->getKey()]);

        $this->assertTrue($allowed['success'] ?? false);
        $this->assertDatabaseHas(Post::class, ['id' => $shown->getKey(), 'is_active' => true]);
    }

    #[Test]
    public function an_index_getter_tool_reaches_only_the_rows_the_index_query_lists(): void
    {
        $listed = Post::factory()->create();
        $unlisted = Post::factory()->create();

        McpScopedPostRepository::$unlisted = [$unlisted->getKey()];

        $result = $this->callTool('mcp-scoped-posts-reachable-posts-getter-tool', ['repositories' => '"all"']);

        $this->assertTrue($result['success'] ?? false);
        $this->assertSame([$listed->getKey()], $result['result']['filtered']);
        $this->assertSame([$listed->getKey()], $result['result']['collected']);
    }

    #[Test]
    public function a_show_getter_tool_does_not_read_a_model_the_show_query_excludes(): void
    {
        $shown = Post::factory()->create(['title' => 'Shown']);
        $notShown = Post::factory()->create(['title' => 'Not shown']);

        McpScopedPostRepository::$unshowable = [$notShown->getKey()];

        $denied = $this->callTool('mcp-scoped-posts-post-title-getter-tool', ['id' => (string) $notShown->getKey()]);

        $this->assertArrayNotHasKey('success', $denied);
        $this->assertTrue($denied['result']['isError'] ?? false);
        $this->assertStringNotContainsString('Not shown', (string) json_encode($denied));

        $allowed = $this->callTool('mcp-scoped-posts-post-title-getter-tool', ['id' => (string) $shown->getKey()]);

        $this->assertTrue($allowed['success'] ?? false);
        $this->assertSame('Shown', $allowed['result']['title']);
    }

    /**
     * The decoded tool result, or the JSON-RPC response when the tool failed without one.
     *
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
