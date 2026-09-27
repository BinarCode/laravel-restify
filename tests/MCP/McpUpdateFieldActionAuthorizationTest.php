<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class McpUpdateFieldActionAuthorizationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'wrapper']);

        Cache::partialMock()
            ->shouldReceive('remember')
            ->andReturnUsing(fn (string $key, mixed $ttl, callable $callback): mixed => $callback());

        Restify::repositories([FieldActionPostMcpRepository::class]);
        Mcp::web('test-field-action-update', RestifyServer::class);
    }

    protected function tearDown(): void
    {
        Restify::$repositories = [];
        FieldActionPostMcpRepository::$canRunDescriptionAction = true;

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    #[TestWith([false, true, 'Original title', 'Original description'], 'denied')]
    #[TestWith([true, false, 'Updated title', 'Actionable Updated description'], 'allowed')]
    public function mcp_update_honours_the_field_action_can_run(
        bool $canRun,
        bool $expectedError,
        string $expectedTitle,
        string $expectedDescription,
    ): void {
        FieldActionPostMcpRepository::$canRunDescriptionAction = $canRun;

        $post = Post::factory()->create([
            'title' => 'Original title',
            'description' => 'Original description',
        ]);

        $result = $this
            ->postJson('/test-field-action-update', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'execute-operation',
                    'arguments' => [
                        'repository' => FieldActionPostMcpRepository::uriKey(),
                        'operation_type' => 'update',
                        'parameters' => [
                            'id' => $post->id,
                            'title' => 'Updated title',
                            'description' => 'Updated description',
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->json('result');

        $this->assertSame($expectedError, $result['isError'] ?? false);

        if ($expectedError) {
            $content = json_decode($result['content'][0]['text'], true);
            $this->assertSame('AUTHORIZATION_ERROR', $content['code']);
        }

        $this->assertDatabaseHas(Post::class, [
            'id' => $post->id,
            'title' => $expectedTitle,
            'description' => $expectedDescription,
        ]);
    }

    #[Test]
    #[TestWith([false, true, 0], 'denied')]
    #[TestWith([true, false, 1], 'allowed')]
    public function mcp_store_honours_the_field_action_can_run(bool $canRun, bool $expectedError, int $expectedPosts): void
    {
        FieldActionPostMcpRepository::$canRunDescriptionAction = $canRun;

        $result = $this
            ->postJson('/test-field-action-update', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'execute-operation',
                    'arguments' => [
                        'repository' => FieldActionPostMcpRepository::uriKey(),
                        'operation_type' => 'store',
                        'parameters' => [
                            'title' => 'Stored title',
                            'description' => 'Stored description',
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->json('result');

        $this->assertSame($expectedError, $result['isError'] ?? false);

        if ($expectedError) {
            $content = json_decode($result['content'][0]['text'], true);
            $this->assertSame('AUTHORIZATION_ERROR', $content['code']);
        }

        $this->assertDatabaseCount(Post::class, $expectedPosts);
    }
}

class FieldActionPostMcpRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-field-action-posts';

    public static bool $canRunDescriptionAction = true;

    public function mcpAllowsUpdate(): bool
    {
        return true;
    }

    public function mcpAllowsStore(): bool
    {
        return true;
    }

    public function fields(RestifyRequest $request): array
    {
        $action = new class extends Action
        {
            public function handle(RestifyRequest $request, Post $post): void
            {
                $post->update([
                    'description' => "Actionable {$request->input('description')}",
                ]);
            }
        };

        return [
            Field::new('title'),
            Field::new('description')->action(
                $action->canRun(fn (): bool => static::$canRunDescriptionAction)
            ),
        ];
    }
}
