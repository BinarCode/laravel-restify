<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\MCP\Tools\Operations\StoreTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class McpStoreToolIntegrationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);
        config(['restify.mcp.mode' => 'direct']);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_mcp_http_store_tool_uses_mcp_specific_fields(): void
    {
        // Create test repository with MCP tools enabled
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

            public function fieldsForMcpStore(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('user_id'),
                    Field::make('mcp_metadata'),
                    Field::make('internal_tracking'),
                ];
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
        Mcp::web('test-restify', RestifyServer::class);

        // Create test data
        PostFactory::many(2);

        // First, get the available tools to verify our tool exists
        $toolsListPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        $this->withoutExceptionHandling();
        $toolsResponse = $this->postJson('/test-restify', $toolsListPayload);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();

        // Find our expected store tool name
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $storeToolName = collect($availableTools)->filter(fn ($name) => str_contains($name,
            'test-posts') && str_contains($name, 'store'))->first();

        $this->assertNotNull($storeToolName,
            'Expected test-posts store tool not found. Available tools: '.implode(', ', $availableTools));

        // Create MCP JSON-RPC 2.0 request payload for calling the store tool
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $storeToolName,
                'arguments' => [
                    'title' => 'Test Post via MCP',
                    'description' => 'Created through MCP store tool',
                    'user_id' => 1,
                ],
            ],
        ];

        // Make HTTP POST request to MCP endpoint
        $response = $this->postJson('/test-restify', $mcpPayload);

        // Assert successful response
        $response->assertOk();

        // Get the response data
        $responseData = $response->json();

        // First check if this is an error response
        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        // Assert JSON-RPC response structure
        $this->assertArrayHasKey('jsonrpc', $responseData);
        $this->assertEquals('2.0', $responseData['jsonrpc']);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals(2, $responseData['id']);
        $this->assertArrayHasKey('result', $responseData);

        // Parse the result content (should be JSON string)
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        // Assert that the store response contains the created record
        $this->assertArrayHasKey('data', $resultContent);
        $this->assertArrayHasKey('attributes', $resultContent['data']);

        $attributes = $resultContent['data']['attributes'];

        // Assert MCP-specific fields that should only appear in MCP store requests
        $this->assertArrayHasKey('mcp_metadata', $attributes);
        $this->assertArrayHasKey('internal_tracking', $attributes);

        // Assert regular fields are also present
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('user_id', $attributes);

        // Assert the created values
        $this->assertEquals('Test Post via MCP', $attributes['title']);
        $this->assertEquals('Created through MCP store tool', $attributes['description']);
        $this->assertEquals(1, $attributes['user_id']);

        // Assert that the record was actually created in the database
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post via MCP',
            'description' => 'Created through MCP store tool',
            'user_id' => 1,
        ]);

        // Verify that the total count of posts increased by 1 (we started with 2, should now have 3)
        $this->assertDatabaseCount('posts', 3);
    }

    /**
     * Test that MCP store tool properly validates required fields and returns validation errors,
     * while also verifying that the tool schema includes MCP-specific fields with proper requirements.
     */
    public function test_mcp_http_store_tool_validated_payload(): void
    {
        // Create test repository with validation rules in MCP store fields
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-validation-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                ];
            }

            public function fieldsForMcpStore(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                    Field::make('description')->required()->rules(['min:10']),
                    Field::make('user_id')->required(),
                    Field::make('mcp_metadata')->resolveCallback(fn () => 'mcp-specific-data'),
                    Field::make('internal_tracking')->resolveCallback(fn () => 'internal-123'),
                ];
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
        Mcp::web('test-validation-restify', RestifyServer::class);

        // First, get the available tools to verify our tool exists
        $toolsListPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        $toolsResponse = $this->postJson('/test-validation-restify', $toolsListPayload);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();

        // Find our expected store tool name
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $storeToolName = collect($availableTools)->filter(fn ($name) => str_contains($name,
            'test-validation-posts') && str_contains($name, 'store'))->first();

        $this->assertNotNull($storeToolName,
            'Expected test-validation-posts store tool not found. Available tools: '.implode(', ', $availableTools));

        // Test Case 1: Missing required description field
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $storeToolName,
                'arguments' => [
                    'title' => 'Test Post via MCP',
                    'user_id' => 1,
                    // Missing required 'description' field
                ],
            ],
        ];

        // Make HTTP POST request to MCP endpoint (should fail validation)
        $response = $this->postJson('/test-validation-restify', $mcpPayload);
        $response->assertOk(); // MCP responses are always 200, errors are in the payload

        $responseData = $response->json();

        // The MCP response should contain validation errors
        $this->assertTrue($responseData['result']['isError']);

        // Parse the error content which should be JSON with validation errors
        $errorContent = $responseData['result']['content'][0]['text'];

        $this->assertSame('The description field is required.', $errorContent);

        // Test Case 2: Test that tool schema includes MCP-specific required fields
        $toolSchema = collect($toolsData['result']['tools'])
            ->firstWhere('name', $storeToolName);

        $this->assertNotNull($toolSchema);
        $this->assertArrayHasKey('inputSchema', $toolSchema);
        $this->assertArrayHasKey('properties', $toolSchema['inputSchema']);

        $properties = $toolSchema['inputSchema']['properties'];

        // Verify MCP-specific fields are in the schema
        $this->assertArrayHasKey('description', $properties);
        $this->assertArrayHasKey('mcp_metadata', $properties);
        $this->assertArrayHasKey('internal_tracking', $properties);

        // Verify required fields are marked as required in schema
        $requiredFields = $toolSchema['inputSchema']['required'];
        $this->assertContains('description', $requiredFields);
        $this->assertContains('user_id', $requiredFields);
    }

    /**
     * The McpToolsManager singleton caches tool instances (and their repositories), so consecutive
     * store calls reuse the same repository. Each call must create a fresh record instead of
     * updating the previously stored one.
     */
    public function test_mcp_http_store_tool_creates_distinct_records_on_consecutive_calls(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-consecutive-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('user_id'),
                ];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpRepository::class,
        ]);

        Mcp::web('test-consecutive-restify', RestifyServer::class);

        $toolsResponse = $this->postJson('/test-consecutive-restify', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);
        $toolsResponse->assertOk();

        $availableTools = collect($toolsResponse->json('result.tools'))->pluck('name')->toArray();
        $storeToolName = collect($availableTools)->filter(fn ($name) => str_contains($name,
            'test-consecutive-posts') && str_contains($name, 'store'))->first();

        $this->assertNotNull($storeToolName,
            'Expected test-consecutive-posts store tool not found. Available tools: '.implode(', ', $availableTools));

        $createdIds = [];

        foreach (['First Department', 'Second Department', 'Third Department'] as $index => $title) {
            $response = $this->postJson('/test-consecutive-restify', [
                'jsonrpc' => '2.0',
                'id' => $index + 2,
                'method' => 'tools/call',
                'params' => [
                    'name' => $storeToolName,
                    'arguments' => [
                        'title' => $title,
                        'user_id' => 1,
                    ],
                ],
            ]);

            $response->assertOk();

            $responseData = $response->json();

            if (isset($responseData['error'])) {
                $this->fail('MCP Error: '.$responseData['error']['message']);
            }

            $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

            $this->assertEquals($title, $resultContent['data']['attributes']['title']);

            $createdIds[] = $resultContent['data']['id'];
        }

        $this->assertCount(3, array_unique($createdIds), 'Each store call must create a new record, not update the previous one.');

        $this->assertDatabaseCount('posts', 3);

        foreach (['First Department', 'Second Department', 'Third Department'] as $title) {
            $this->assertDatabaseHas('posts', ['title' => $title]);
        }
    }

    public function test_mcp_update_after_store_targets_the_created_record(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'articles';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('user_id'),
                ];
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

        Restify::repositories([
            $mcpRepository::class,
        ]);

        Mcp::web('test-store-then-update-restify', RestifyServer::class);

        $toolsResponse = $this->postJson('/test-store-then-update-restify', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);
        $toolsResponse->assertOk();

        $availableTools = collect($toolsResponse->json('result.tools'))->pluck('name')->toArray();
        $storeToolName = collect($availableTools)->first(fn ($name) => str_ends_with($name, 'articles-store-tool'));
        $updateToolName = collect($availableTools)->first(fn ($name) => str_ends_with($name, 'articles-update-tool'));

        $this->assertNotNull($storeToolName);
        $this->assertNotNull($updateToolName);

        $storeResponse = $this->postJson('/test-store-then-update-restify', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $storeToolName,
                'arguments' => [
                    'title' => 'Original Title',
                    'user_id' => 1,
                ],
            ],
        ]);
        $storeResponse->assertOk();

        $storedId = json_decode($storeResponse->json('result.content.0.text'), true)['data']['id'];

        $updateResponse = $this->postJson('/test-store-then-update-restify', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => $updateToolName,
                'arguments' => [
                    'id' => $storedId,
                    'title' => 'Updated Title',
                ],
            ],
        ]);
        $updateResponse->assertOk();

        $updated = json_decode($updateResponse->json('result.content.0.text'), true);

        $this->assertEquals($storedId, $updated['data']['id']);
        $this->assertEquals('Updated Title', $updated['data']['attributes']['title']);

        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['id' => $storedId, 'title' => 'Updated Title']);
    }

    public function test_mcp_store_tools_from_different_repositories_do_not_pollute_each_other(): void
    {
        $firstRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'alpha';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('user_id'),
                ];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        $secondRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'beta';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('user_id'),
                ];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $firstRepository::class,
            $secondRepository::class,
        ]);

        Mcp::web('test-multi-store-restify', RestifyServer::class);

        $availableTools = collect($this->postJson('/test-multi-store-restify', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ])->json('result.tools'))->pluck('name')->toArray();

        $firstStoreTool = collect($availableTools)->first(fn ($name) => str_ends_with($name, 'alpha-store-tool'));
        $secondStoreTool = collect($availableTools)->first(fn ($name) => str_ends_with($name, 'beta-store-tool'));

        $this->assertNotNull($firstStoreTool);
        $this->assertNotNull($secondStoreTool);

        $callStore = function (string $toolName, string $title, int $id): array {
            $response = $this->postJson('/test-multi-store-restify', [
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => [
                        'title' => $title,
                        'user_id' => 1,
                    ],
                ],
            ]);
            $response->assertOk();

            return json_decode($response->json('result.content.0.text'), true);
        };

        $first = $callStore($firstStoreTool, 'From First Repository', 2);
        $second = $callStore($secondStoreTool, 'From Second Repository', 3);

        $this->assertEquals('From First Repository', $first['data']['attributes']['title']);
        $this->assertEquals('From Second Repository', $second['data']['attributes']['title']);
        $this->assertNotEquals($first['data']['id'], $second['data']['id']);

        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseHas('posts', ['title' => 'From First Repository']);
        $this->assertDatabaseHas('posts', ['title' => 'From Second Repository']);
    }

    public function test_operation_tool_resolves_a_fresh_repository_instance_per_call(): void
    {
        $mcpRepository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'fresh-instance-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                ];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([
            $mcpRepository::class,
        ]);

        $tool = new StoreTool($mcpRepository::class);

        $this->assertNotSame(
            $tool->repository(),
            $tool->repository(),
            'Each call must resolve a fresh repository instance so state cannot leak between MCP tool invocations.'
        );
    }
}
