<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\CommentFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request as McpToolRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class McpIndexMaxPerPageTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Restify::$repositories = [];

        parent::tearDown();
    }

    #[Test]
    #[TestWith([50, 100, 55, 50], 'cap 50: an int perPage above the cap is clamped')]
    #[TestWith([null, 100.0, 3, 100], 'no cap: a float perPage is honoured, not defaulted')]
    #[TestWith([50, 100.0, 55, 50], 'cap 50: a float perPage above the cap is clamped')]
    #[TestWith([50, 20.0, 3, 20], 'cap 50: a float perPage under the cap is untouched')]
    #[TestWith([50, 1e20, 3, 50], 'cap 50: an unrepresentable-as-int float perPage is clamped, not a crash')]
    #[TestWith([null, 1e20, 3, PHP_INT_MAX], 'no cap: an unrepresentable-as-int float perPage saturates, not a crash')]
    public function it_normalizes_a_float_per_page_on_the_mcp_index_path(
        ?int $maxPerPage,
        int|float $requestedPerPage,
        int $seedCount,
        int $expectedMetaPerPage,
    ): void {
        config(['restify.pagination.max_per_page' => $maxPerPage]);

        CommentFactory::many($seedCount);

        $result = $this->callIndexTool(perPage: $requestedPerPage);

        $this->assertCount(min($seedCount, $expectedMetaPerPage), $result['data']);
        $this->assertSame($expectedMetaPerPage, $result['meta']['per_page']);
    }

    /**
     * @return array<string, mixed>
     */
    private function callIndexTool(int|float $perPage): array
    {
        $repositoryClass = new class extends Repository
        {
            use HasMcpTools;

            public static string $model = Comment::class;

            public static string $uriKey = 'mcp-max-per-page-comments';
        };

        Restify::repositories([$repositoryClass::class]);

        $tool = new IndexTool($repositoryClass::class);

        $response = $tool->handle(new McpToolRequest(['perPage' => $perPage]));

        return $response->getStructuredContent();
    }
}
