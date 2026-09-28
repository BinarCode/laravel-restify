<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class McpDeleteToolAuthorizationTest extends IntegrationTestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'mcp-delete-authorization';

    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([McpDeleteAuthorizationPostRepository::class]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['restify.post.delete']);
        Restify::$repositories = [];

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function the_direct_delete_tool_refuses_when_the_policy_denies_delete(): void
    {
        $post = Post::factory()->create();
        $_SERVER['restify.post.delete'] = false;

        $this->callDirectDeleteTool($post)
            ->assertOk()
            ->assertJsonPath('result.isError', true)
            ->assertJsonPath('result.content.0.text', 'This action is unauthorized.');

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function the_wrapper_delete_operation_reports_an_authorization_error_when_the_policy_denies_delete(): void
    {
        $post = Post::factory()->create();
        $_SERVER['restify.post.delete'] = false;

        $response = $this->callWrapperDeleteOperation($post)
            ->assertOk()
            ->assertJsonPath('result.isError', true);

        $content = json_decode($response->json('result.content.0.text'), true);
        $this->assertSame('AUTHORIZATION_ERROR', $content['code']);

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function the_direct_delete_tool_deletes_when_the_policy_allows_delete(): void
    {
        $post = Post::factory()->create();
        $_SERVER['restify.post.delete'] = true;

        $this->callDirectDeleteTool($post)
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.deleted', true);

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function the_wrapper_delete_operation_deletes_when_the_policy_allows_delete(): void
    {
        $post = Post::factory()->create();
        $_SERVER['restify.post.delete'] = true;

        $this->callWrapperDeleteOperation($post)
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
    }

    private function callDirectDeleteTool(Post $post): TestResponse
    {
        config(['restify.mcp.mode' => 'direct']);
        Mcp::web(self::ENDPOINT, RestifyServer::class);

        return $this->callTool('mcp-delete-authorization-posts-delete-tool', [
            'id' => (string) $post->getKey(),
        ]);
    }

    private function callWrapperDeleteOperation(Post $post): TestResponse
    {
        config(['restify.mcp.mode' => 'wrapper']);
        Mcp::web(self::ENDPOINT, RestifyServer::class);

        return $this->callTool('execute-operation', [
            'repository' => 'mcp-delete-authorization-posts',
            'operation_type' => 'delete',
            'parameters' => ['id' => (string) $post->getKey()],
        ]);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callTool(string $name, array $arguments): TestResponse
    {
        return $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ]);
    }
}

class McpDeleteAuthorizationPostRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-delete-authorization-posts';

    public function mcpAllowsDelete(): bool
    {
        return true;
    }
}
