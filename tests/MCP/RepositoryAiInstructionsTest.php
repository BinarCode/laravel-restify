<?php

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Server\McpServiceProvider;

class RepositoryAiInstructionsTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_returns_markdown_ai_instructions_for_a_repository(): void
    {
        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static $model = Post::class;

            public static string $uriKey = 'ai-posts';

            public function fields(RestifyRequest $request): array
            {
                return [
                    Field::make('title')->required(),
                    Field::make('description'),
                ];
            }

            public function actions(RestifyRequest $request): array
            {
                return [PublishPostAction::new()];
            }

            public function mcpAllowsIndex(): bool
            {
                return true;
            }

            public function mcpAllowsStore(): bool
            {
                return true;
            }

            public function mcpAllowsActions(): bool
            {
                return true;
            }
        };

        Restify::repositories([$repository::class]);

        $response = $this->getJson('/'.trim(\Binaryk\LaravelRestify\Restify::path('ai-posts/ai-instructions'), '/'));

        $response->assertOk();

        $body = $response->json();

        $this->assertSame('ai-posts', $body['repository']);

        $instructions = $body['instructions'];

        // Front matter
        $this->assertStringStartsWith("---\nrepository: ai-posts", $instructions);
        $this->assertStringContainsString('generated_by: laravel-restify', $instructions);

        // CRUD tools
        $this->assertStringContainsString('## ai-posts-index-tool', $instructions);
        $this->assertStringContainsString('## ai-posts-store-tool', $instructions);

        // Action tool
        $this->assertStringContainsString('publish-post-action', $instructions);

        // Parameters with required/optional flags from the store schema
        $this->assertStringContainsString('**Parameters**', $instructions);
        $this->assertStringContainsString('`title`', $instructions);
        $this->assertStringContainsString('required', $instructions);
    }

    public function test_returns_404_when_repository_has_no_mcp_tools(): void
    {
        $repository = new class extends Repository
        {
            public static $model = Post::class;

            public static string $uriKey = 'no-mcp-posts';

            public function fields(RestifyRequest $request): array
            {
                return [Field::make('title')];
            }
        };

        Restify::repositories([$repository::class]);

        $this->getJson('/'.trim(\Binaryk\LaravelRestify\Restify::path('no-mcp-posts/ai-instructions'), '/'))
            ->assertNotFound();
    }
}
