<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;

class McpServerHardeningTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => true]);

        Cache::partialMock()
            ->shouldReceive('remember')
            ->andReturnUsing(fn ($key, $ttl, $callback) => $callback())
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

    public function test_request_parameter_cannot_override_wrapper_mode(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);

        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'override-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([$repository::class]);
        Mcp::web('test-override-restify', RestifyServer::class);

        $response = $this->postJson('/test-override-restify?mode=direct', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => ['mode' => 'direct'],
        ]);
        $response->assertOk();

        $toolNames = collect($response->json('result.tools'))->pluck('name')->toArray();

        $this->assertContains('execute-operation', $toolNames);
        $this->assertNotContains('override-posts-store-tool', $toolNames);
    }

    public function test_read_only_mode_hides_write_operations_from_get_repository_operations(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);
        config(['restify.mcp.read_only' => true]);

        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'readonly-posts';

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

            public function mcpAllowsDelete(): bool
            {
                return true;
            }
        };

        Restify::repositories([$repository::class]);
        Mcp::web('test-readonly-ops-restify', RestifyServer::class);

        $response = $this->postJson('/test-readonly-ops-restify', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'get-repository-operations',
                'arguments' => ['repository' => 'readonly-posts'],
            ],
        ]);
        $response->assertOk();

        $result = json_decode($response->json('result.content.0.text'), true);
        $types = collect($result['operations'])->pluck('type')->toArray();

        $this->assertContains('index', $types);
        $this->assertNotContains('store', $types);
        $this->assertNotContains('update', $types);
        $this->assertNotContains('delete', $types);
    }

    public function test_read_only_mode_rejects_execute_operation_for_write(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);
        config(['restify.mcp.read_only' => true]);

        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'readonly-execute-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }
        };

        Restify::repositories([$repository::class]);
        Mcp::web('test-readonly-execute-restify', RestifyServer::class);

        $response = $this->postJson('/test-readonly-execute-restify', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'readonly-execute-posts',
                    'operation_type' => 'store',
                    'parameters' => ['title' => 'Should Not Be Created'],
                ],
            ],
        ]);
        $response->assertOk();

        $this->assertTrue($response->json('result.isError'));
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_local_filesystem_path_is_rejected_by_default(): void
    {
        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'file-guard-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        $localFile = tempnam(sys_get_temp_dir(), 'restify-mcp');
        file_put_contents($localFile, 'local file content');

        $this->assertNull($repository->resolveUploadedFileFromInput($localFile));
        $this->assertFalse($repository::isSafePublicUrl($localFile));

        config(['restify.mcp.files.allow_local_paths' => true]);

        $this->assertInstanceOf(
            UploadedFile::class,
            $repository->resolveUploadedFileFromInput($localFile)
        );

        unlink($localFile);
    }

    public function test_private_and_reserved_ip_urls_are_rejected(): void
    {
        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'ssrf-guard-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        $this->assertFalse($repository::isSafePublicUrl('http://127.0.0.1/secret'));
        $this->assertFalse($repository::isSafePublicUrl('http://169.254.169.254/latest/meta-data'));
        $this->assertFalse($repository::isSafePublicUrl('http://192.168.0.1/'));
        $this->assertFalse($repository::isSafePublicUrl('http://10.0.0.5/'));
        $this->assertFalse($repository::isSafePublicUrl('http://[::1]/'));
        $this->assertFalse($repository::isSafePublicUrl('ftp://example.com/file.txt'));
        $this->assertFalse($repository::isSafePublicUrl('file:///etc/passwd'));

        $this->assertTrue($repository::isSafePublicUrl('http://93.184.216.34/index.html'));
        $this->assertTrue($repository::isSafePublicUrl('https://93.184.216.34/index.html'));
    }
}
