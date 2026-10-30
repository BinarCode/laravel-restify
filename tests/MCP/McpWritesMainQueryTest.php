<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class McpWritesMainQueryTest extends IntegrationTestCase
{
    private const ENDPOINT = 'mcp-writes-main-query';

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpWritesMainQueryPostRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function the_update_tool_cannot_reach_a_record_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->callTool('mcp-writes-main-query-posts-update-tool', [
            'id' => (string) $hidden->getKey(),
            'title' => 'Changed',
        ])->assertOk()->assertJsonPath('result.isError', true);

        $this->callTool('mcp-writes-main-query-posts-update-tool', [
            'id' => (string) $visible->getKey(),
            'title' => 'Changed',
        ])->assertOk()->assertJsonPath('result.isError', false);

        $this->assertDatabaseHas(Post::class, ['id' => $hidden->getKey(), 'title' => 'Original']);
        $this->assertDatabaseHas(Post::class, ['id' => $visible->getKey(), 'title' => 'Changed']);
    }

    #[Test]
    public function the_delete_tool_cannot_reach_a_record_hidden_by_main_query(): void
    {
        [$hidden, $visible] = $this->hidePost();

        $this->callTool('mcp-writes-main-query-posts-delete-tool', [
            'id' => (string) $hidden->getKey(),
        ])->assertOk()->assertJsonPath('result.isError', true);

        $this->callTool('mcp-writes-main-query-posts-delete-tool', [
            'id' => (string) $visible->getKey(),
        ])->assertOk()->assertJsonPath('result.isError', false);

        $this->assertModelExists($hidden);
        $this->assertModelMissing($visible);
    }

    /**
     * @return array{Post, Post}
     */
    private function hidePost(): array
    {
        $hidden = Post::factory()->create(['title' => 'Original']);
        $visible = Post::factory()->create(['title' => 'Original']);

        McpWritesMainQueryPostRepository::$hiddenId = $hidden->getKey();

        return [$hidden, $visible];
    }

    /**
     * @param  array<string, string>  $arguments
     */
    private function callTool(string $name, array $arguments): TestResponse
    {
        return $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ]);
    }
}

class McpWritesMainQueryPostRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-writes-main-query-posts';

    public static int|string|null $hiddenId = null;

    public static function mainQuery(RestifyRequest $request, $query)
    {
        return $query->clone()->whereKeyNot(static::$hiddenId);
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
        ];
    }

    public function mcpAllowsUpdate(): bool
    {
        return true;
    }

    public function mcpAllowsDelete(): bool
    {
        return true;
    }
}
