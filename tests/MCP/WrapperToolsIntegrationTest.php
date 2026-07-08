<?php

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class WrapperToolsIntegrationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);

        // Enable wrapper mode
        config(['restify.mcp.mode' => 'wrapper']);

        // Mock cache to prevent database cache table errors in tests
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
        // Clear Restify repositories to prevent affecting subsequent tests
        Restify::$repositories = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_wrapper_mode_exposes_only_4_wrapper_tools(): void
    {
        // Create test repositories
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        // Register the repository with Restify
        Restify::repositories([
            $mcpRepository::class,
        ]);

        // Register MCP server route
        Mcp::web('test-wrapper-restify', RestifyServer::class);

        // Get the list of available tools
        $toolsListPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        $this->withoutExceptionHandling();
        $toolsResponse = $this->postJson('/test-wrapper-restify', $toolsListPayload);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();
        $tools = collect($toolsData['result']['tools']);

        // Assert the 4 wrapper tools exist
        $toolNames = $tools->pluck('name')->toArray();
        $this->assertContains('discover-repositories', $toolNames);
        $this->assertContains('get-repository-operations', $toolNames);
        $this->assertContains('get-operation-details', $toolNames);
        $this->assertContains('execute-operation', $toolNames);

        // Assert no direct repository tools are exposed (repository operations should be wrapped)
        $this->assertNotContains('test-posts-index-tool', $toolNames);
        $this->assertNotContains('test-posts-store-tool', $toolNames);

        // Note: Static tools (like global-search) are always exposed regardless of mode
        // This is expected behavior - we only wrap repository-based operations
    }

    public function test_discover_repositories_tool_lists_mcp_enabled_repositories(): void
    {
        // Create test repositories - one with MCP, one without
        $mcpEnabledRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'mcp-enabled-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        $nonMcpRepository = new class extends Repository
        {
            public static $model = Post::class;

            public static string $uriKey = 'non-mcp-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        // Register both repositories
        Restify::repositories([
            $mcpEnabledRepository::class,
            $nonMcpRepository::class,
        ]);

        Mcp::web('test-discover-restify', RestifyServer::class);

        // Call discover-repositories tool
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'discover-repositories',
                'arguments' => [],
            ],
        ];

        $response = $this->postJson('/test-discover-restify', $mcpPayload);
        $response->assertOk();

        $responseData = $response->json();
        $this->assertArrayHasKey('result', $responseData);

        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        // Assert success
        $this->assertTrue($resultContent['success']);
        $this->assertArrayHasKey('repositories', $resultContent);

        $repositories = collect($resultContent['repositories']);

        // Assert only MCP-enabled repository is returned
        $this->assertCount(1, $repositories);
        $this->assertEquals('mcp-enabled-posts', $repositories->first()['name']);

        // Assert non-MCP repository is not included
        $this->assertFalse($repositories->contains('name', 'non-mcp-posts'));

        // Assert metadata is included
        $repo = $repositories->first();
        $this->assertArrayHasKey('label', $repo);
        $this->assertArrayHasKey('operations', $repo);
        $this->assertContains('index', $repo['operations']);
    }

    public function test_discover_repositories_tool_supports_search(): void
    {
        Restify::$repositories = []; // Clear any previous repos

        $postRepo = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        $userRepo = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'users';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('name')];
            }
        };

        Restify::repositories([$postRepo::class, $userRepo::class]);
        Mcp::web('test-search-restify', RestifyServer::class);

        // Test 1: Search for "user" should find users repository
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'discover-repositories',
                'arguments' => [
                    'search' => 'users',
                ],
            ],
        ];

        $response = $this->postJson('/test-search-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);
        $repositories = collect($resultContent['repositories']);

        // Should include users repository (search is case-insensitive)
        $userRepo = $repositories->firstWhere('name', 'users');
        $this->assertNotNull($userRepo, 'Users repository should be found');

        // Test 2: Search for "posts" should find posts repository
        $mcpPayload2 = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'discover-repositories',
                'arguments' => [
                    'search' => 'posts',
                ],
            ],
        ];

        $response2 = $this->postJson('/test-search-restify', $mcpPayload2);
        $response2->assertOk();

        $resultContent2 = json_decode($response2->json()['result']['content'][0]['text'], true);
        $repositories2 = collect($resultContent2['repositories']);

        // Should include posts repository
        $postsRepo = $repositories2->firstWhere('name', 'posts');
        $this->assertNotNull($postsRepo, 'Posts repository should be found');
    }

    public function test_get_repository_operations_tool_returns_operations_list(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-operations-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }

            public function mcpAllowsUpdate(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-ops-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-repository-operations',
                'arguments' => [
                    'repository' => 'test-operations-posts',
                ],
            ],
        ];

        $response = $this->postJson('/test-ops-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);

        // Assert structure
        $this->assertTrue($resultContent['success']);
        $this->assertEquals('test-operations-posts', $resultContent['repository']);
        $this->assertArrayHasKey('operations', $resultContent);
        $this->assertArrayHasKey('summary', $resultContent);

        // Assert operations
        $operations = collect($resultContent['operations']);
        $this->assertCount(3, $operations); // index, store, update

        $operationTypes = $operations->pluck('type')->toArray();
        $this->assertContains('index', $operationTypes);
        $this->assertContains('store', $operationTypes);
        $this->assertContains('update', $operationTypes);

        // Assert summary
        $this->assertEquals(3, $resultContent['summary']['crud_operations_count']);
    }

    public function test_get_repository_operations_includes_safety_annotations_per_operation(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-annotations-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsUpdate(): bool
            {
                return true;
            }

            public function mcpAllowsDelete(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-annotations-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-repository-operations',
                'arguments' => [
                    'repository' => 'test-annotations-posts',
                ],
            ],
        ];

        $response = $this->postJson('/test-annotations-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);
        $operations = collect($resultContent['operations']);

        $index = $operations->firstWhere('type', 'index');
        $this->assertSame(['readOnlyHint' => true], $index['annotations']);

        $update = $operations->firstWhere('type', 'update');
        $this->assertSame(['idempotentHint' => true], $update['annotations']);

        $delete = $operations->firstWhere('type', 'delete');
        $this->assertSame(['destructiveHint' => true, 'idempotentHint' => true], $delete['annotations']);
    }

    public function test_get_repository_operations_rejects_non_mcp_repositories(): void
    {
        $nonMcpRepository = new class extends Repository
        {
            public static $model = Post::class;

            public static string $uriKey = 'non-mcp-repo';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        Restify::repositories([$nonMcpRepository::class]);
        Mcp::web('test-reject-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-repository-operations',
                'arguments' => [
                    'repository' => 'non-mcp-repo',
                ],
            ],
        ];

        $response = $this->postJson('/test-reject-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);

        // Should return error
        $this->assertArrayHasKey('error', $resultContent);
        $this->assertStringContainsString('does not have MCP tools enabled', $resultContent['error']);
        $this->assertEquals('INVALID_REPOSITORY', $resultContent['code']);
    }

    public function test_get_operation_details_returns_schema_and_examples(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-details-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                    Field::make('description'),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-details-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-operation-details',
                'arguments' => [
                    'repository' => 'test-details-posts',
                    'operation_type' => 'index',
                ],
            ],
        ];

        $response = $this->postJson('/test-details-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);

        // Assert structure
        $this->assertTrue($resultContent['success']);
        $this->assertEquals('index', $resultContent['type']);
        $this->assertArrayHasKey('schema', $resultContent);
        $this->assertArrayHasKey('examples', $resultContent);

        // Assert schema contains expected fields
        // Schema is now wrapped in ObjectType following JSON Schema spec
        $schema = $resultContent['schema'];
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('page', $schema['properties']);
        $this->assertArrayHasKey('perPage', $schema['properties']);
        $this->assertArrayHasKey('search', $schema['properties']);
        $this->assertArrayHasKey('include', $schema['properties']);

        // Assert examples are provided
        $this->assertNotEmpty($resultContent['examples']);
        $this->assertArrayHasKey('description', $resultContent['examples'][0]);
        $this->assertArrayHasKey('parameters', $resultContent['examples'][0]);
    }

    public function test_execute_operation_creates_record_via_wrapper(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-execute-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('user_id'),
                ];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-execute-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'test-execute-posts',
                    'operation_type' => 'store',
                    'parameters' => [
                        'title' => 'Created via Wrapper',
                        'description' => 'This was created through the execute-operation wrapper tool',
                        'user_id' => 1,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/test-execute-restify', $mcpPayload);
        $response->assertOk();

        $responseData = $response->json();
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        // Assert successful creation
        $this->assertArrayHasKey('data', $resultContent);
        $this->assertArrayHasKey('attributes', $resultContent['data']);

        $attributes = $resultContent['data']['attributes'];
        $this->assertEquals('Created via Wrapper', $attributes['title']);
        $this->assertEquals('This was created through the execute-operation wrapper tool', $attributes['description']);

        // Assert database record
        $this->assertDatabaseHas('posts', [
            'title' => 'Created via Wrapper',
            'description' => 'This was created through the execute-operation wrapper tool',
            'user_id' => 1,
        ]);
    }

    public function test_execute_operation_lists_records_via_wrapper(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-index-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-index-restify', RestifyServer::class);

        // Create test data
        PostFactory::many(3);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'test-index-posts',
                    'operation_type' => 'index',
                    'parameters' => [
                        'page' => 1,
                        'perPage' => 10,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/test-index-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);

        // Assert paginated response
        $this->assertArrayHasKey('data', $resultContent);
        $this->assertArrayHasKey('meta', $resultContent);
        $this->assertCount(3, $resultContent['data']);
        $this->assertEquals(3, $resultContent['meta']['total']);
    }

    public function test_execute_operation_validates_permissions(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-permissions-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            // Store is NOT allowed
            public function mcpAllowsStore(): bool
            {
                return false;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-permissions-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'test-permissions-posts',
                    'operation_type' => 'store',
                    'parameters' => [
                        'title' => 'Should Not Be Created',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/test-permissions-restify', $mcpPayload);
        $response->assertOk();

        $resultContent = json_decode($response->json()['result']['content'][0]['text'], true);

        // Should return error about operation not found (because it's not discovered when mcpAllowsStore is false)
        $this->assertArrayHasKey('error', $resultContent);
        $this->assertStringContainsString("Operation 'store' not found", $resultContent['error']);
    }

    public function test_discover_repositories_returns_structured_content_matching_text(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'structured-discover-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-structured-discover-restify', RestifyServer::class);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'discover-repositories',
                'arguments' => [],
            ],
        ];

        $response = $this->postJson('/test-structured-discover-restify', $mcpPayload);
        $response->assertOk();

        $result = $response->json()['result'];

        $this->assertArrayHasKey('structuredContent', $result);

        $textPayload = json_decode($result['content'][0]['text'], true);

        $this->assertSame($textPayload, $result['structuredContent']);
        $this->assertTrue($result['structuredContent']['success']);
        $this->assertSame(
            'structured-discover-posts',
            $result['structuredContent']['repositories'][0]['name']
        );
    }

    public function test_execute_operation_index_returns_structured_content_matching_text(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'structured-index-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-structured-index-restify', RestifyServer::class);

        PostFactory::many(2);

        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'structured-index-posts',
                    'operation_type' => 'index',
                    'parameters' => [
                        'page' => 1,
                        'perPage' => 10,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/test-structured-index-restify', $mcpPayload);
        $response->assertOk();

        $result = $response->json()['result'];

        $this->assertArrayHasKey('structuredContent', $result);

        $textPayload = json_decode($result['content'][0]['text'], true);

        $this->assertSame($textPayload, $result['structuredContent']);
        $this->assertArrayHasKey('data', $result['structuredContent']);
        $this->assertArrayHasKey('meta', $result['structuredContent']);
        $this->assertCount(2, $result['structuredContent']['data']);
        $this->assertSame(2, $result['structuredContent']['meta']['total']);
    }

    public function test_wrapper_mode_complete_workflow(): void
    {
        // This test demonstrates the complete workflow: discover -> operations -> details -> execute

        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'workflow-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                    Field::make('description'),
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([$mcpRepository::class]);
        Mcp::web('test-workflow-restify', RestifyServer::class);

        // Step 1: Discover repositories
        $discoverPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'discover-repositories',
                'arguments' => [],
            ],
        ];

        $discoverResponse = $this->postJson('/test-workflow-restify', $discoverPayload);
        $discoverResponse->assertOk();
        $discoverResult = json_decode($discoverResponse->json()['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('repositories', $discoverResult);
        $repositoryName = $discoverResult['repositories'][0]['name'];
        $this->assertEquals('workflow-posts', $repositoryName);

        // Step 2: Get operations for the repository
        $operationsPayload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-repository-operations',
                'arguments' => [
                    'repository' => $repositoryName,
                ],
            ],
        ];

        $operationsResponse = $this->postJson('/test-workflow-restify', $operationsPayload);
        $operationsResponse->assertOk();
        $operationsResult = json_decode($operationsResponse->json()['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('operations', $operationsResult);
        $operations = collect($operationsResult['operations']);
        $this->assertTrue($operations->contains('type', 'store'));

        // Step 3: Get details for store operation
        $detailsPayload = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-operation-details',
                'arguments' => [
                    'repository' => $repositoryName,
                    'operation_type' => 'store',
                ],
            ],
        ];

        $detailsResponse = $this->postJson('/test-workflow-restify', $detailsPayload);
        $detailsResponse->assertOk();
        $detailsResult = json_decode($detailsResponse->json()['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('schema', $detailsResult);
        // Schema is now wrapped in ObjectType, so check for properties
        $this->assertArrayHasKey('properties', $detailsResult['schema']);

        // Step 4: Execute the store operation
        $executePayload = [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => $repositoryName,
                    'operation_type' => 'store',
                    'parameters' => [
                        'title' => 'Complete Workflow Test',
                        'description' => 'Created through complete wrapper workflow',
                    ],
                ],
            ],
        ];

        $executeResponse = $this->postJson('/test-workflow-restify', $executePayload);
        $executeResponse->assertOk();
        $executeResult = json_decode($executeResponse->json()['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('data', $executeResult);
        $this->assertEquals('Complete Workflow Test', $executeResult['data']['attributes']['title']);

        // Verify in database
        $this->assertDatabaseHas('posts', [
            'title' => 'Complete Workflow Test',
            'description' => 'Created through complete wrapper workflow',
        ]);
    }
}
