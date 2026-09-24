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
use PHPUnit\Framework\Attributes\TestWith;

class McpActionsCanRunAuthorizationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'test-can-run-actions';

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
    #[TestWith([false, 'Not authorized to run this action.'], 'denied')]
    #[TestWith([true, null], 'allowed')]
    public function standalone_action_can_run_is_reported_over_mcp(bool $allowed, ?string $expectedError): void
    {
        $action = $this->standaloneAction()->canRun(fn (Request $request, ?Model $model): bool => $allowed);

        $actionToolName = $this->mcpRepository($action);

        $response = $this->postJson('/'.self::ENDPOINT, [
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

        if ($allowed) {
            $this->assertTrue($resultContent['success']);

            return;
        }

        $this->assertArrayHasKey('error', $resultContent);
        $this->assertEquals($expectedError, $resultContent['error']);
    }

    #[Test]
    public function index_action_with_repositories_and_a_denied_row_is_reported_as_a_structured_error_over_mcp(): void
    {
        $action = $this->bulkAction()->canRun(fn (Request $request, ?Model $model): bool => false);

        $actionToolName = $this->mcpRepository($action);

        $posts = Post::factory()->count(2)->create(['is_active' => false]);

        $response = $this->postJson('/'.self::ENDPOINT, [
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

    #[Test]
    public function show_action_runs_can_run_exactly_once_over_mcp(): void
    {
        $callCount = 0;

        $action = $this->showAction()->canRun(function (Request $request, ?Model $model) use (&$callCount): bool {
            $callCount++;

            return true;
        });

        $actionToolName = $this->mcpRepository($action);

        $post = $this->mockPost();

        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'id' => (string) $post->id,
                ],
            ],
        ]);

        $response->assertOk();

        $resultContent = json_decode($response->json('result.content.0.text'), true);

        $this->assertTrue($resultContent['success']);
        $this->assertSame(1, $callCount);
    }

    private function mcpRepository(Action $action): string
    {
        $repositoryUriKey = 'mcp-can-run-posts';

        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-can-run-posts';

            public static Action $providedAction;

            public function actions(RestifyRequest $request): array
            {
                return [self::$providedAction];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        $mcpPostRepository::$providedAction = $action;

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);

        return $this->findActionToolName(self::ENDPOINT, $repositoryUriKey, $action->uriKey());
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

    private function showAction(): Action
    {
        return (new class extends Action
        {
            public static $uriKey = 'can-run-show-action';

            public function handle(ActionRequest $request, Post $post): JsonResponse
            {
                return response()->json(['ok' => true]);
            }
        })->onlyOnShow();
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
