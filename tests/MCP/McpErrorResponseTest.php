<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
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

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    private function callExecuteOperation(string $route, array $arguments): array
    {
        return $this->callTool($route, 'execute-operation', $arguments);
    }

    private function callTool(string $route, string $tool, array $arguments): array
    {
        $response = $this->postJson($route, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $tool,
                'arguments' => $arguments,
            ],
        ]);

        $response->assertOk();

        return $response->json();
    }

    private function assertInvalidOperation(array $result): string
    {
        $this->assertArrayNotHasKey('error', $result, 'Expected a tool error result, got a JSON-RPC error.');
        $this->assertTrue($result['result']['isError']);

        $text = $result['result']['content'][0]['text'];
        $content = json_decode($text, true);
        $this->assertIsArray($content, "Tool error is not a structured error payload: {$text}");
        $this->assertEquals('INVALID_OPERATION', $content['code']);

        return $content['error'];
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

    public function test_unknown_operation_type_returns_invalid_operation_with_valid_types(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-unknown-operation-type', RestifyServer::class);

        $message = $this->assertInvalidOperation($this->callExecuteOperation('/test-unknown-operation-type', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'nope',
            'parameters' => [],
        ]));

        $this->assertStringContainsString('"nope"', $message);
        $this->assertStringContainsString('index, show, store, update, delete, profile, action, getter', $message);
        $this->assertStringContainsString('operation_type "action" or "getter" with operation_name', $message);
    }

    public function test_php_error_in_operation_returns_execution_error_and_is_reported(): void
    {
        Exceptions::fake();
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-php-error', RestifyServer::class);

        $result = $this->callExecuteOperation('/test-php-error', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'action',
            'operation_name' => 'error-action',
            'parameters' => [],
        ]);

        $this->assertArrayNotHasKey('error', $result, 'Expected a tool error result, got a JSON-RPC error.');
        $this->assertTrue($result['result']['isError']);
        $content = json_decode($result['result']['content'][0]['text'], true);
        $this->assertIsArray($content);
        $this->assertEquals('EXECUTION_ERROR', $content['code']);
        Exceptions::assertReported(\TypeError::class);
    }

    public function test_action_uri_key_as_operation_type_hints_the_action_call(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-action-uri-key-hint', RestifyServer::class);

        $message = $this->assertInvalidOperation($this->callExecuteOperation('/test-action-uri-key-hint', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'validated-action',
            'parameters' => [],
        ]));

        $this->assertSame(
            '"validated-action" is an action on "mcp-error-users": call operation_type="action", operation_name="validated-action".',
            $message
        );
    }

    public function test_getter_uri_key_as_operation_type_hints_the_getter_call(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-getter-uri-key-hint', RestifyServer::class);

        $message = $this->assertInvalidOperation($this->callExecuteOperation('/test-getter-uri-key-hint', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'posts-index-getter',
            'parameters' => [],
        ]));

        $this->assertSame(
            '"posts-index-getter" is a getter on "mcp-error-users": call operation_type="getter", operation_name="posts-index-getter".',
            $message
        );
    }

    public function test_get_operation_details_with_unknown_operation_type_returns_invalid_operation(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-details-unknown-operation-type', RestifyServer::class);

        $message = $this->assertInvalidOperation($this->callTool('/test-details-unknown-operation-type', 'get-operation-details', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'validated-action',
        ]));

        $this->assertStringContainsString('operation_type="action", operation_name="validated-action"', $message);
    }

    public function test_action_operation_type_still_executes_the_action(): void
    {
        Restify::repositories([UserErrorRepository::class]);
        Mcp::web('test-action-happy-path', RestifyServer::class);

        $result = $this->callExecuteOperation('/test-action-happy-path', [
            'repository' => 'mcp-error-users',
            'operation_type' => 'action',
            'operation_name' => 'ok-action',
            'parameters' => [],
        ]);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertFalse($result['result']['isError'] ?? false);
        $this->assertStringContainsString('"ok":true', str_replace(' ', '', $result['result']['content'][0]['text']));
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

    public function mcpAllowsGetters(): bool
    {
        return true;
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            PostsIndexGetter::new(),
        ];
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            (new class extends Action
            {
                public static $uriKey = 'ok-action';

                public function handle(ActionRequest $request): JsonResponse
                {
                    return response()->json(['ok' => true]);
                }
            })->standalone(),

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
                public static $uriKey = 'error-action';

                public function handle(ActionRequest $request): JsonResponse
                {
                    throw new \TypeError('Broken action.');
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
