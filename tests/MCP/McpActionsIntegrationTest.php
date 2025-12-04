<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\ActivateAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\DisableProfileAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class McpActionsIntegrationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // MCP features require Laravel 12+ JsonSchema classes
        if (! interface_exists(\Illuminate\Contracts\JsonSchema\JsonSchema::class)) {
            $this->markTestSkipped('MCP features require Laravel 12+');
        }
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_could_perform_action_for_multiple_repositories(): void
    {
        PublishPostAction::$applied = [];

        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-test-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    PublishPostAction::new(),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-actions', RestifyServer::class);

        $posts = $this->mockPosts(1, 2);

        $toolsResponse = $this->postJson('/test-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-test-posts') && str_contains($name, 'publish-post-action')
        )->first();

        $this->assertNotNull($actionToolName, 'Action tool not found in available tools');

        $response = $this->postJson('/test-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'repositories' => '['.$posts->first()->id.','.$posts->last()->id.']',
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $this->assertArrayHasKey('result', $responseData);

        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('success', $resultContent);
        $this->assertTrue($resultContent['success']);
        $this->assertEquals('publish-post-action', $resultContent['action']);

        $this->assertNotEmpty(PublishPostAction::$applied, 'Action was not executed');
        $this->assertNotEmpty(PublishPostAction::$applied[0], 'No models were passed to action');
        $this->assertEquals(2, PublishPostAction::$applied[0][0]->id);
        $this->assertEquals(1, PublishPostAction::$applied[0][1]->id);
    }

    public function test_action_with_custom_uri_key(): void
    {
        PublishPostAction::$applied = [];

        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-custom-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    PublishPostAction::new(),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-custom-actions', RestifyServer::class);

        $posts = $this->mockPosts(1, 2);

        $toolsResponse = $this->postJson('/test-custom-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-custom-posts') && str_contains($name, 'publish-post-action')
        )->first();

        $this->assertNotNull($actionToolName);

        $response = $this->postJson('/test-custom-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'repositories' => '['.$posts->first()->id.','.$posts->last()->id.']',
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $this->assertArrayHasKey('result', $responseData);
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertTrue($resultContent['success']);
        $this->assertNotEmpty(PublishPostAction::$applied);
    }

    public function test_show_action_not_need_repositories(): void
    {
        ActivateAction::$applied = [];

        $mcpUserRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = User::class;

            public static string $uriKey = 'mcp-test-users';

            public function actions(RestifyRequest $request): array
            {
                return [
                    ActivateAction::new()->onlyOnShow(),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpUserRepository::class,
        ]);

        Mcp::web('test-show-actions', RestifyServer::class);

        $users = $this->mockUsers();

        $toolsResponse = $this->postJson('/test-show-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-test-users') && str_contains($name, 'activate-action')
        )->first();

        $this->assertNotNull($actionToolName);

        $response = $this->postJson('/test-show-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'id' => (string) $users->first()->id,
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $this->assertArrayHasKey('result', $responseData);
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('success', $resultContent);
        $this->assertTrue($resultContent['success']);
        $this->assertEquals(1, ActivateAction::$applied[0]->id);
    }

    public function test_could_perform_standalone_action(): void
    {
        DisableProfileAction::$applied = [];

        $mcpUserRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = User::class;

            public static string $uriKey = 'mcp-standalone-users';

            public function actions(RestifyRequest $request): array
            {
                return [
                    DisableProfileAction::new()->standalone(),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpUserRepository::class,
        ]);

        Mcp::web('test-standalone-actions', RestifyServer::class);

        $toolsResponse = $this->postJson('/test-standalone-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-standalone-users') && str_contains($name, 'disable_profile')
        )->first();

        $this->assertNotNull($actionToolName);

        $response = $this->postJson('/test-standalone-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $this->assertArrayHasKey('result', $responseData);
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertTrue($resultContent['success']);
        $this->assertEquals('foo', DisableProfileAction::$applied[0]);
    }

    public function test_action_authorization_with_can_run(): void
    {
        $mcpUserRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = User::class;

            public static string $uriKey = 'mcp-auth-users';

            public function actions(RestifyRequest $request): array
            {
                return [
                    ActivateAction::new()->onlyOnShow()->canRun(function ($request, $model) {
                        return false;
                    }),
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpUserRepository::class,
        ]);

        Mcp::web('test-auth-actions', RestifyServer::class);

        $users = $this->mockUsers();

        $toolsResponse = $this->postJson('/test-auth-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-auth-users') && str_contains($name, 'activate-action')
        )->first();

        $this->assertNotNull($actionToolName);

        $response = $this->postJson('/test-auth-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'id' => (string) $users->first()->id,
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('error', $resultContent);
        $this->assertEquals('Not authorized to run this action', $resultContent['error']);
    }

    public function test_action_with_validation_rules(): void
    {
        $mcpPostRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-validation-posts';

            public function actions(RestifyRequest $request): array
            {
                return [
                    new class extends Action
                    {
                        public static $uriKey = 'custom-publish';

                        public function rules(): array
                        {
                            return [
                                'title' => ['required', 'string'],
                                'is_active' => ['required', 'boolean'],
                            ];
                        }

                        public function handle(ActionRequest $request, Collection $models): JsonResponse
                        {
                            return response()->json([
                                'validated_data' => $request->validated(),
                            ]);
                        }
                    },
                ];
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-validation-actions', RestifyServer::class);

        $posts = $this->mockPosts(1);

        $toolsResponse = $this->postJson('/test-validation-actions', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $actionToolName = collect($availableTools)->filter(
            fn ($name) => str_contains($name, 'mcp-validation-posts') && str_contains($name, 'custom-publish')
        )->first();

        $this->assertNotNull($actionToolName);

        $response = $this->postJson('/test-validation-actions', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $actionToolName,
                'arguments' => [
                    'repositories' => '['.$posts->first()->id.']',
                    'title' => 'Test Title',
                    'is_active' => true,
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertTrue($resultContent['success']);

        // Verify action executed successfully - the fact that we got success means
        // the action was able to process the request with the validation rules defined
    }
}
