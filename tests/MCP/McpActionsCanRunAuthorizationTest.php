<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class McpActionsCanRunAuthorizationTest extends IntegrationTestCase
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
    public function standalone_action_denied_by_can_run_is_reported_as_a_structured_error_over_mcp(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-auth-denied-standalone-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    $this->standaloneAction()->canRun(fn (Request $request, ?Model $model): bool => false),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
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
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-auth-denied-standalone', RestifyServer::class);

        $actionToolName = $this->findActionToolName('test-auth-denied-standalone', 'mcp-auth-denied-standalone-posts', 'can-run-standalone-action');

        $response = $this->postJson('/test-auth-denied-standalone', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertArrayHasKey('error', $resultContent);
        $this->assertEquals('Not authorized to run this action', $resultContent['error']);
    }

    #[Test]
    public function standalone_action_allowed_by_can_run_executes_over_mcp(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-auth-allowed-standalone-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    $this->standaloneAction()->canRun(fn (Request $request, ?Model $model): bool => true),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
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
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-auth-allowed-standalone', RestifyServer::class);

        $actionToolName = $this->findActionToolName('test-auth-allowed-standalone', 'mcp-auth-allowed-standalone-posts', 'can-run-standalone-action');

        $response = $this->postJson('/test-auth-allowed-standalone', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertTrue($resultContent['success']);
    }

    #[Test]
    public function index_action_with_repositories_and_a_denied_row_is_reported_as_a_structured_error_over_mcp(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-auth-denied-index-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    $this->bulkAction()->canRun(fn (Request $request, ?Model $model): bool => false),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
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
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-auth-denied-index', RestifyServer::class);

        $posts = Post::factory()->count(2)->create(['is_active' => false]);

        $actionToolName = $this->findActionToolName('test-auth-denied-index', 'mcp-auth-denied-index-posts', 'can-run-bulk-action');

        $response = $this->postJson('/test-auth-denied-index', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'repositories' => '['.$posts->pluck('id')->implode(',').']',
                ],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertArrayHasKey('error', $resultContent);
        $this->assertEquals('Not authorized to run this action.', $resultContent['error']);

        foreach ($posts as $post) {
            $this->assertDatabaseHas(Post::class, [
                'id' => $post->id,
                'is_active' => false,
            ]);
        }
    }

    private function findActionToolName(string $endpoint, string $repositoryUriKey, string $actionUriKey): string
    {
        $toolsResponse = $this->postJson("/{$endpoint}", [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $availableTools = Collection::make($toolsResponse->json('result.tools'))->pluck('name');

        $actionToolName = $availableTools->first(
            fn (string $name): bool => str_contains($name, $repositoryUriKey) && str_contains($name, $actionUriKey)
        );

        $this->assertNotNull($actionToolName, 'Action tool not found in available tools');

        return $actionToolName;
    }
}
