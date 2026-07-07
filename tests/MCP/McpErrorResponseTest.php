<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class McpErrorResponseTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);
        config(['restify.mcp.mode' => 'wrapper']);

        Cache::partialMock()
            ->shouldReceive('remember')
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            })
            ->shouldReceive('flush')
            ->andReturn(true);
    }

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    private function callExecuteOperation(string $route, array $arguments): array
    {
        $response = $this->postJson($route, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => $arguments,
            ],
        ]);

        $response->assertOk();

        return $response->json();
    }

    public function test_missing_repository_returns_real_mcp_error(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-missing-repository', RestifyServer::class);

        $result = $this->callExecuteOperation('/test-missing-repository', [
            'operation_type' => 'index',
        ]);

        $this->assertTrue($result['result']['isError']);

        $content = json_decode($result['result']['content'][0]['text'], true);
        $this->assertEquals('Repository parameter is required', $content['error']);
        $this->assertEquals('MISSING_PARAMETER', $content['code']);
    }

    public function test_action_validation_errors_surface_per_field_detail(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-action-validation', RestifyServer::class);

        $result = $this->callExecuteOperation('/test-action-validation', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'action',
            'operation_name' => 'validated-action',
            'parameters' => [],
        ]);

        $this->assertTrue($result['result']['isError']);

        $content = json_decode($result['result']['content'][0]['text'], true);
        $this->assertEquals('VALIDATION_ERROR', $content['code']);
        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('title', $content['errors']);
    }

    public function test_action_authorization_failure_returns_is_error(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-action-authorization', RestifyServer::class);

        $result = $this->callExecuteOperation('/test-action-authorization', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'action',
            'operation_name' => 'forbidden-action',
            'parameters' => [],
        ]);

        $this->assertTrue($result['result']['isError']);

        $content = json_decode($result['result']['content'][0]['text'], true);
        $this->assertEquals('AUTHORIZATION_ERROR', $content['code']);
    }
}

class UserErrorRepository extends Repository
{
    use HasMcpTools;

    public static $model = User::class;

    public static string $uriKey = 'mcp-error-users';

    public function mcpAllowsIndex(): bool
    {
        return true;
    }

    public function mcpAllowsActions(): bool
    {
        return true;
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            (new class extends Action
            {
                public static $uriKey = 'validated-action';

                public function handle(ActionRequest $request): JsonResponse
                {
                    Validator::make($request->all(), [
                        'title' => ['required', 'string'],
                    ])->validate();

                    return response()->json(['ok' => true]);
                }
            })->standalone(),

            (new class extends Action
            {
                public static $uriKey = 'forbidden-action';

                public function handle(ActionRequest $request): JsonResponse
                {
                    throw new AuthorizationException('Not allowed.');
                }
            })->standalone(),
        ];
    }
}
