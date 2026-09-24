<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class McpGettersCanRunAuthorizationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    #[Test]
    public function getter_authorization_with_can_run_denies_over_mcp(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-auth-denied-getter-posts';

            public function getters(RestifyRequest $request): array
            {
                return [
                    PostsShowGetter::new()->onlyOnShow()->canRun(fn (Request $request, ?Model $model): bool => false),
                ];
            }

            public function mcpAllowsGetters(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-auth-denied-getters', RestifyServer::class);

        $post = $this->mockPost();

        $getterToolName = $this->findGetterToolName('test-auth-denied-getters', 'mcp-auth-denied-getter-posts');

        $response = $this->postJson('/test-auth-denied-getters', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $getterToolName,
                'arguments' => [
                    'id' => (string) $post->id,
                ],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertArrayHasKey('error', $resultContent);
        $this->assertEquals('Not authorized to run this getter.', $resultContent['error']);
    }

    #[Test]
    public function getter_authorization_with_can_run_allows_over_mcp(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-auth-allowed-getter-posts';

            public function getters(RestifyRequest $request): array
            {
                return [
                    PostsShowGetter::new()->onlyOnShow()->canRun(fn (Request $request, ?Model $model): bool => true),
                ];
            }

            public function mcpAllowsGetters(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-auth-allowed-getters', RestifyServer::class);

        $post = $this->mockPost();

        $getterToolName = $this->findGetterToolName('test-auth-allowed-getters', 'mcp-auth-allowed-getter-posts');

        $response = $this->postJson('/test-auth-allowed-getters', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $getterToolName,
                'arguments' => [
                    'id' => (string) $post->id,
                ],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertTrue($resultContent['success']);
    }

    private function findGetterToolName(string $endpoint, string $repositoryUriKey): string
    {
        $toolsResponse = $this->postJson("/{$endpoint}", [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $availableTools = Collection::make($toolsResponse->json('result.tools'))->pluck('name');

        $getterToolName = $availableTools->first(
            fn (string $name): bool => str_contains($name, $repositoryUriKey) && str_contains($name, 'posts-show-getter')
        );

        $this->assertNotNull($getterToolName, 'Getter tool not found in available tools');

        return $getterToolName;
    }
}
