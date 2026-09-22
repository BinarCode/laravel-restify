<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\CommentFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class McpIndexMaxPerPageTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    #[Test]
    public function it_clamps_the_requested_per_page_on_the_mcp_index_path(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        CommentFactory::many(55);

        $repository = new class extends Repository
        {
            use HasMcpTools;

            public static string $model = Comment::class;

            public static string $uriKey = 'mcp-max-per-page-comments';
        };

        Restify::repositories([$repository::class]);

        $mcpRequest = new McpIndexRequest(['perPage' => 100]);

        $result = $repository->indexTool($mcpRequest);

        $this->assertCount(50, $result['data']);
        $this->assertSame(50, $result['meta']['per_page']);
    }
}
