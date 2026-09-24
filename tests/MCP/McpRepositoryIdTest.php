<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The MCP update and delete tools call the repository's `update()` and
 * `destroy()` hooks without going through a route, so nothing on those paths
 * may read the `{repositoryId}` route segment: the hook's `$repositoryId`
 * argument is the only id they carry.
 */
class McpRepositoryIdTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpRepositoryIdPostRepository::class]);

        Mcp::web('mcp-repository-id', RestifyServer::class);
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['McpRepositoryIdPostRepository.update.repositoryId'],
            $_SERVER['McpRepositoryIdPostRepository.destroy.repositoryId'],
        );

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function the_mcp_update_tool_passes_the_tool_id_to_the_update_hook_without_a_route(): void
    {
        $post = PostFactory::new()->create(['title' => 'Original title']);

        $this->callTool('mcp-repository-id-posts-update-tool', [
            'id' => (string) $post->getKey(),
            'title' => 'Updated via MCP',
        ])->assertOk()->assertJsonMissingPath('error')->assertJsonPath('result.isError', false);

        $this->assertSame((string) $post->getKey(), $_SERVER['McpRepositoryIdPostRepository.update.repositoryId']);
        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Updated via MCP']);
    }

    #[Test]
    public function the_mcp_delete_tool_passes_the_tool_id_to_the_destroy_hook_without_a_route(): void
    {
        $post = PostFactory::new()->create();

        $this->callTool('mcp-repository-id-posts-delete-tool', [
            'id' => (string) $post->getKey(),
        ])->assertOk()->assertJsonMissingPath('error')->assertJsonPath('result.isError', false);

        $this->assertSame((string) $post->getKey(), $_SERVER['McpRepositoryIdPostRepository.destroy.repositoryId']);
        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
    }

    /**
     * @param  array<string, string>  $arguments
     */
    private function callTool(string $name, array $arguments): TestResponse
    {
        return $this->postJson('/mcp-repository-id', [
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

class McpRepositoryIdPostRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-repository-id-posts';

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

    public function update(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['McpRepositoryIdPostRepository.update.repositoryId'] = $repositoryId;

        return parent::update($request, $repositoryId);
    }

    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $_SERVER['McpRepositoryIdPostRepository.destroy.repositoryId'] = $repositoryId;

        return parent::destroy($request, $repositoryId);
    }
}
