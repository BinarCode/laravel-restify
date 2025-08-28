<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpRequest;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Server\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class McpFieldsIntegrationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    public function test_repository_uses_mcp_specific_field_methods(): void
    {
        $repository = new class extends Repository {
            public static $model = Post::class;

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                ];
            }

            public function fieldsForMcpIndex(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('internal_id'),
                    Field::make('mcp_metadata'),
                ];
            }

            public function fieldsForMcpShow(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('internal_id'),
                    Field::make('debug_info'),
                    Field::make('processing_status'),
                ];
            }
        };

        // Regular request should use fields() method
        $regularRequest = new RestifyRequest;
        $regularFields = $repository->collectFields($regularRequest);
        $regularFieldNames = $regularFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(2, $regularFields);
        $this->assertContains('title', $regularFieldNames);
        $this->assertContains('description', $regularFieldNames);
        $this->assertNotContains('internal_id', $regularFieldNames);
        $this->assertNotContains('mcp_metadata', $regularFieldNames);

        // MCP index request should use fieldsForMcpIndex() method
        $mcpIndexRequest = new McpRequest(['params' => ['name' => 'posts-index-tool']]);
        $mcpIndexFields = $repository->collectFields($mcpIndexRequest);
        $mcpIndexFieldNames = $mcpIndexFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(4, $mcpIndexFields);
        $this->assertContains('title', $mcpIndexFieldNames);
        $this->assertContains('description', $mcpIndexFieldNames);
        $this->assertContains('internal_id', $mcpIndexFieldNames);
        $this->assertContains('mcp_metadata', $mcpIndexFieldNames);
        $this->assertNotContains('debug_info', $mcpIndexFieldNames);

        // MCP show request should use fieldsForMcpShow() method
        $mcpShowRequest = new McpRequest(['params' => ['name' => 'posts-show-tool']]);
        $mcpShowFields = $repository->collectFields($mcpShowRequest);
        $mcpShowFieldNames = $mcpShowFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(5, $mcpShowFields);
        $this->assertContains('title', $mcpShowFieldNames);
        $this->assertContains('description', $mcpShowFieldNames);
        $this->assertContains('internal_id', $mcpShowFieldNames);
        $this->assertContains('debug_info', $mcpShowFieldNames);
        $this->assertContains('processing_status', $mcpShowFieldNames);
        $this->assertNotContains('mcp_metadata', $mcpShowFieldNames);
    }

    public function test_mcp_request_falls_back_to_regular_methods_when_mcp_methods_missing(): void
    {
        $repository = new class extends Repository {
            public static $model = Post::class;

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                ];
            }

            public function fieldsForIndex(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('category'),
                ];
            }
        };

        // MCP request without fieldsForMcpIndex should fall back to fieldsForIndex
        $mcpIndexRequest = new McpRequest(['params' => ['name' => 'posts-index-tool']]);
        $mcpIndexFields = $repository->collectFields($mcpIndexRequest);
        $mcpIndexFieldNames = $mcpIndexFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(2, $mcpIndexFields);
        $this->assertContains('title', $mcpIndexFieldNames);
        $this->assertContains('category', $mcpIndexFieldNames);
        $this->assertNotContains('description', $mcpIndexFieldNames);

        // MCP request without fieldsForMcpShow should fall back to fields()
        $mcpShowRequest = new McpRequest(['params' => ['name' => 'posts-show-tool']]);
        $mcpShowFields = $repository->collectFields($mcpShowRequest);
        $mcpShowFieldNames = $mcpShowFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(2, $mcpShowFields);
        $this->assertContains('title', $mcpShowFieldNames);
        $this->assertContains('description', $mcpShowFieldNames);
        $this->assertNotContains('category', $mcpShowFieldNames);
    }

    public function test_mcp_getter_request_uses_fields_for_mcp_getter(): void
    {
        $repository = new class extends Repository {
            public static $model = Post::class;

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                ];
            }

            public function fieldsForMcpGetter(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('analytics_data'),
                    Field::make('performance_metrics'),
                ];
            }
        };

        $mcpGetterRequest = new McpRequest(['params' => ['name' => 'analytics-getter-tool']]);
        $mcpGetterFields = $repository->collectFields($mcpGetterRequest);
        $mcpGetterFieldNames = $mcpGetterFields->map(fn($field) => $field->getAttribute())->toArray();

        $this->assertCount(3, $mcpGetterFields);
        $this->assertContains('title', $mcpGetterFieldNames);
        $this->assertContains('analytics_data', $mcpGetterFieldNames);
        $this->assertContains('performance_metrics', $mcpGetterFieldNames);
    }

    public function test_mcp_http_integration_uses_mcp_specific_fields(): void
    {
        // Create test repository with MCP tools enabled
        $mcpRepository = new class extends Repository {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                ];
            }

            public function fieldsForMcpIndex(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('user_id'),
                    Field::make('mcp_metadata')->resolveCallback(fn() => 'mcp-specific-data'),
                    Field::make('internal_tracking')->resolveCallback(fn() => 'internal-123'),
                ];
            }

            public function mcpAllowsIndex(): bool
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

        $toolsResponse = $this->postJson('/test-restify', $toolsListPayload);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();

        // Find our expected tool name
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $indexToolName = collect($availableTools)->filter(fn($name) => str_contains($name,
                'test-posts') && str_contains($name, 'index'))->first();

        $this->assertNotNull($indexToolName,
            'Expected test-posts index tool not found. Available tools: '.implode(', ', $availableTools));

        // Create MCP JSON-RPC 2.0 request payload for calling the index tool
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $indexToolName,
                'arguments' => [
                    'perPage' => 10,
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

        // Assert that MCP-specific fields are present in the response
        $this->assertArrayHasKey('data', $resultContent);
        $this->assertNotEmpty($resultContent['data']);

        $firstItem = $resultContent['data'][0];
        $this->assertArrayHasKey('attributes', $firstItem);

        $attributes = $firstItem['attributes'];

        // Assert MCP-specific fields that should only appear in MCP requests
        $this->assertArrayHasKey('mcp_metadata', $attributes);
        $this->assertArrayHasKey('internal_tracking', $attributes);
        $this->assertEquals('mcp-specific-data', $attributes['mcp_metadata']);
        $this->assertEquals('internal-123', $attributes['internal_tracking']);

        // Assert regular fields are also present
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('user_id', $attributes);
    }

    public function test_mcp_http_integration_with_relationships_uses_mcp_specific_fields(): void
    {
        // Create simple MCP-enabled Post repository
        $mcpPostRepository = new class extends Repository {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-posts-with-user';

            public static function include(): array
            {
                return [
                    BelongsTo::make('user', UserWithMcpIndexFields::class),
                ];
            }

            public static array $related = ['user'];

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('user_id'),
                ];
            }

            public function fieldsForMcpIndex(RestifyRequest $request): array
            {
                return [
                    Field::make('title'),
                    Field::make('description'),
                    Field::make('user_id'),
                    Field::make('mcp_post_metadata')->resolveCallback(fn() => 'post-mcp-specific-data'),
                    Field::make('post_analytics')->resolveCallback(fn() => 'post-analytics-data'),
                    BelongsTo::make('user'),  // Will use the MCP-enabled UserRepository
                ];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }
        };

        // Register both repositories with Restify, replacing the existing UserRepository
        Restify::repositories([
            UserWithMcpIndexFields::class,
            $mcpPostRepository::class,
        ]);

        // Register MCP server route
        Mcp::web('test-restify-relations', RestifyServer::class);

        // Create test data with relationships
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Post with User',
            'description' => 'A post that belongs to a user',
        ]);

        // First, get available tools
        $toolsListPayload = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ];

        $toolsResponse = $this->postJson('/test-restify-relations', $toolsListPayload);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();


        ray($toolsData);
        // Find the post index tool name
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $postIndexToolName = collect($availableTools)->filter(
            fn($name) => str_contains($name, 'test-posts-with-user') && str_contains($name, 'index')
        )->first();

        $this->assertNotNull($postIndexToolName,
            'Expected test-posts-with-user index tool not found. Available tools: '.implode(', ', $availableTools));

        // Create MCP request with relationship inclusion
        $mcpPayload = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $postIndexToolName,
                'arguments' => [
                    'perPage' => 10,
                    'related' => 'user',
                ],
            ],
        ];

        // Make HTTP POST request to MCP endpoint
        $response = $this->postJson('/test-restify-relations', $mcpPayload);
        $response->assertOk();

        $responseData = $response->json();

        // Check for errors
        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        // Assert JSON-RPC response structure
        $this->assertArrayHasKey('result', $responseData);

        // Parse the result content
        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('data', $resultContent);
        $this->assertNotEmpty($resultContent['data']);

        $firstItem = $resultContent['data'][0];

        // Assert Post MCP-specific fields
        $attributes = $firstItem['attributes'];
        $this->assertArrayHasKey('mcp_post_metadata', $attributes);
        $this->assertArrayHasKey('post_analytics', $attributes);
        $this->assertEquals('post-mcp-specific-data', $attributes['mcp_post_metadata']);
        $this->assertEquals('post-analytics-data', $attributes['post_analytics']);

        // Assert regular post fields are present
        $this->assertArrayHasKey('title', $attributes);
        $this->assertArrayHasKey('description', $attributes);
        $this->assertArrayHasKey('user_id', $attributes);

        // Assert relationship data is present
        $this->assertArrayHasKey('relationships', $firstItem);
        $this->assertArrayHasKey('user', $firstItem['relationships']);

        $userRelationship = $firstItem['relationships']['user']['attributes'];

        // This would happen if the MCP-enabled anonymous repository was used
        $this->assertEquals('user-mcp-specific-data', $userRelationship['user_mcp_data']);
        $this->assertEquals('user-internal-123', $userRelationship['internal_user_tracking']);
        $this->assertEquals('admin-access-only', $userRelationship['admin_notes']);
        echo "\n✅ SUCCESS: MCP fields are working because MCP-enabled repository was found!\n";

        // Also check basic fields are present
        $this->assertArrayHasKey('name', $userRelationship);
        $this->assertArrayHasKey('email', $userRelationship);
        $this->assertEquals('John Doe', $userRelationship['name']);
        $this->assertEquals('john@example.com', $userRelationship['email']);
        $this->assertArrayNotHasKey('description', $userRelationship);
    }

    public function test_mcp_relationship_respects_has_mcp_tools_trait(): void
    {
        // Create MCP-enabled Post repository that references the regular user
        $mcpPostRepository = new class extends Repository {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'test-posts-regular-user';

            public static function include(): array
            {
                return [
                    BelongsTo::make('user', UserWithoutMcpToolsFields::class)
                ];
            }

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

        // Register repositories - the regular user repository should be found first for User models
        Restify::repositories([
            UserWithoutMcpToolsFields::class,
            $mcpPostRepository::class,
        ]);

        Mcp::web('test-regular-relations', RestifyServer::class);

        // Create test data
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Post with Regular User',
            'description' => 'This post belongs to a regular user repository',
        ]);

        // Get available tools
        $toolsResponse = $this->postJson('/test-regular-relations', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => [],
        ]);
        $toolsResponse->assertOk();

        $toolsData = $toolsResponse->json();
        $availableTools = collect($toolsData['result']['tools'])->pluck('name')->toArray();
        $postIndexToolName = collect($availableTools)->filter(
            fn($name) => str_contains($name, 'test-posts-regular-user') && str_contains($name, 'index')
        )->first();

        $this->assertNotNull($postIndexToolName);

        // Make MCP request
        $response = $this->postJson('/test-regular-relations', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => $postIndexToolName,
                'arguments' => [
                    'perPage' => 10,
                    'related' => 'user',
                ],
            ],
        ]);

        $response->assertOk();
        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $this->fail('MCP Error: '.$responseData['error']['message']);
        }

        $resultContent = json_decode($responseData['result']['content'][0]['text'], true);

        $this->assertArrayHasKey('data', $resultContent);
        $this->assertNotEmpty($resultContent['data']);

        $firstItem = $resultContent['data'][0];

        $this->assertArrayNotHasKey('relationships', $firstItem);
    }
}


class UserWithMcpIndexFields extends Repository
{
    use HasMcpTools;

    public static $model = User::class;

    public static string $uriKey = 'users';

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('name'),
            Field::make('email'),
        ];
    }

    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            Field::make('name'),
            Field::make('email'),
            Field::make('user_mcp_data')->resolveCallback(fn() => 'user-mcp-specific-data'),
            Field::make('internal_user_tracking')->resolveCallback(fn() => 'user-internal-123'),
            Field::make('admin_notes')->resolveCallback(fn() => 'admin-access-only'),
        ];
    }

    public function mcpAllowsIndex(): bool
    {
        return true;
    }
}

class UserWithoutMcpToolsFields extends Repository
{
    public static $model = User::class;

    public static string $uriKey = 'users';

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('name'),
            Field::make('email'),
        ];
    }

    public function mcpAllowsIndex(): bool
    {
        return true;
    }
}
