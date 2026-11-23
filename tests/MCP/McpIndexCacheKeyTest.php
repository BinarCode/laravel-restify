<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\IndexCachePostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request as McpToolRequest;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * With the index cache enabled, two calls of the MCP index tool share a cached page only when they would get the
 * same response. The arguments of the tool arrive in the body, and the query string of the MCP endpoint is merged
 * into the request of every tool.
 *
 * Three posts are listed, newest first: Gamma (sport, active), Beta (news, inactive) and Alpha (news, active).
 */
class McpIndexCacheKeyTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private const ENDPOINT = '/mcp-index-cache';

    private int $calls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->enableRepositoryCache();

        Restify::repositories([IndexCachePostRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $author = User::factory()->create();

        foreach ([['Alpha', 'news', true], ['Beta', 'news', false], ['Gamma', 'sport', true]] as [$title, $category, $active]) {
            Post::factory()->create([
                'user_id' => $author->id,
                'edited_by' => $author->id,
                'title' => $title,
                'category' => $category,
                'is_active' => $active,
            ]);
        }

        $this->recordQueries();
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  list<string>  $firstTitles
     * @param  array<string, mixed>  $second
     * @param  list<string>  $secondTitles
     */
    #[Test]
    #[DataProvider('arguments_that_differ')]
    public function calls_with_different_arguments_get_their_own_cached_page(
        array $first,
        array $firstTitles,
        array $second,
        array $secondTitles,
    ): void {
        $this->onAMissThenOnAHit(function (string $round) use ($first, $firstTitles, $second, $secondTitles): void {
            $this->assertSame($firstTitles, $this->titles($this->callIndexTool($first)), $round);
            $this->assertSame($secondTitles, $this->titles($this->callIndexTool($second)), $round);
        });

        $this->assertServedFromTheCache(calls: 2);
    }

    /**
     * @return array<string, array{array<string, mixed>, list<string>, array<string, mixed>, list<string>}>
     */
    public static function arguments_that_differ(): array
    {
        return [
            'two match filters' => [
                ['title' => 'Alpha'], ['Alpha'],
                ['title' => 'Beta'], ['Beta'],
            ],
            'no match filter, then one' => [
                [], ['Gamma', 'Beta', 'Alpha'],
                ['title' => 'Alpha'], ['Alpha'],
            ],
            'a match filter and its negation' => [
                ['title' => 'Alpha'], ['Alpha'],
                ['-title' => 'Alpha'], ['Gamma', 'Beta'],
            ],
            'two values of a boolean match filter' => [
                ['is_active' => true], ['Gamma', 'Alpha'],
                ['is_active' => false], ['Beta'],
            ],
            'two sorts' => [
                ['sort' => 'title'], ['Alpha', 'Beta', 'Gamma'],
                ['sort' => '-title'], ['Gamma', 'Beta', 'Alpha'],
            ],
            'two search terms' => [
                ['search' => 'Alp'], ['Alpha'],
                ['search' => 'Gam'], ['Gamma'],
            ],
            'two page sizes' => [
                ['perPage' => 1], ['Gamma'],
                ['perPage' => 2], ['Gamma', 'Beta'],
            ],
            'two pages' => [
                ['perPage' => 1, 'page' => 2], ['Beta'],
                ['perPage' => 1, 'page' => 3], ['Alpha'],
            ],
            'two values of an argument read in indexQuery()' => [
                ['in_category' => 'news'], ['Beta', 'Alpha'],
                ['in_category' => 'sport'], ['Gamma'],
            ],
        ];
    }

    #[Test]
    public function a_call_with_a_relationship_and_one_without_get_their_own_cached_page(): void
    {
        $relationships = fn (array $arguments): array => array_keys($this->callIndexTool($arguments)['data'][0]['relationships'] ?? []);

        $this->onAMissThenOnAHit(function (string $round) use ($relationships): void {
            $this->assertSame([], $relationships([]), $round);
            $this->assertSame(['editor'], $relationships(['include' => 'editor']), $round);
            $this->assertSame(['editor'], $relationships(['related' => 'editor']), $round);
        });

        $this->assertServedFromTheCache(calls: 3);
    }

    #[Test]
    public function the_query_string_of_the_mcp_endpoint_is_part_of_the_key(): void
    {
        $this->onAMissThenOnAHit(function (string $round): void {
            $this->assertSame(['Alpha'], $this->titles($this->callIndexTool([], '?title=Alpha')), $round);
            $this->assertSame(['Beta'], $this->titles($this->callIndexTool([], '?title=Beta')), $round);
            $this->assertSame(['Gamma'], $this->titles($this->callIndexTool([], '?in_category=sport')), $round);
            $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles($this->callIndexTool([])), $round);
            $this->assertSame(['Beta'], $this->titles($this->callIndexTool(['title' => 'Beta'], '?in_category=news')), $round);
        });

        $this->assertServedFromTheCache(calls: 5);
    }

    #[Test]
    public function the_mcp_index_and_the_http_index_get_their_own_cached_page(): void
    {
        $this->onAMissThenOnAHit(function (string $round): void {
            $http = $this->getJson(IndexCachePostRepository::route())->assertOk();
            $mcp = $this->callIndexTool([]);

            $this->assertSame(['title', 'description', 'category', 'is_active'], array_keys($http->json('data.0.attributes')), $round);
            $this->assertSame(['title'], array_keys($mcp['data'][0]['attributes']), $round);
            $this->assertStringEndsWith('/index-cache-posts', $http->json('meta.path'), $round);
            $this->assertStringEndsWith(self::ENDPOINT, $mcp['meta']['path'], $round);
        });

        $this->assertServedFromTheCache(calls: 2);
    }

    /**
     * A tool called in process builds its request from the current one, so it has the URL and the input of the
     * HTTP index it is called after. Only the kind of request tells the two apart.
     */
    #[Test]
    public function a_tool_called_in_process_does_not_share_the_cached_page_of_the_current_http_index(): void
    {
        $this->onAMissThenOnAHit(function (string $round): void {
            $http = $this->getJson(IndexCachePostRepository::route())->assertOk();

            $mcp = (new IndexTool(IndexCachePostRepository::class))
                ->handle(new McpToolRequest([]))
                ->getStructuredContent();

            $this->assertSame(['title', 'description', 'category', 'is_active'], array_keys($http->json('data.0.attributes')), $round);
            $this->assertSame(['title'], array_keys($mcp['data'][0]['attributes']), $round);
            $this->assertSame($http->json('meta.path'), $mcp['meta']['path'], $round);
        });

        $this->assertServedFromTheCache(calls: 2);
    }

    #[Test]
    public function an_identical_call_is_served_from_the_cache_whatever_its_json_rpc_id_and_the_order_of_its_arguments(): void
    {
        $arguments = ['title' => 'Alpha', 'include' => 'editor', 'sort' => 'title'];

        $this->assertSame(['Alpha'], $this->titles($this->callIndexTool($arguments)));

        $onMiss = count($this->selectsAgainst(Post::class));

        $this->recordQueries();

        $this->assertSame(['Alpha'], $this->titles($this->callIndexTool(array_reverse($arguments, true))));

        $this->assertGreaterThan(1, $onMiss);
        $this->assertServedFromTheCache(calls: 1);
    }

    #[Test]
    public function a_write_to_the_model_invalidates_the_cached_pages_of_the_tool(): void
    {
        $this->assertSame(['Beta', 'Alpha'], $this->titles($this->callIndexTool(['in_category' => 'news'])));
        $this->assertSame(['Gamma'], $this->titles($this->callIndexTool(['in_category' => 'sport'])));

        Post::query()->where('title', 'Beta')->firstOrFail()->update(['category' => 'sport']);

        $this->assertSame(['Alpha'], $this->titles($this->callIndexTool(['in_category' => 'news'])));
        $this->assertSame(['Gamma', 'Beta'], $this->titles($this->callIndexTool(['in_category' => 'sport'])));
    }

    /**
     * Run the calls twice: the first run fills the cache, the second is served from it.
     *
     * @param  Closure(string): void  $calls
     */
    private function onAMissThenOnAHit(Closure $calls): void
    {
        $calls('on a cache miss');

        $this->recordQueries();

        $calls('on a cache hit');
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callIndexTool(array $arguments, string $query = ''): array
    {
        $response = $this->postJson(self::ENDPOINT.$query, [
            'jsonrpc' => '2.0',
            'id' => ++$this->calls,
            'method' => 'tools/call',
            'params' => [
                'name' => 'index-cache-posts-index-tool',
                'arguments' => (object) $arguments,
            ],
        ])->assertOk();

        $this->assertFalse($response->json('result.isError'), (string) $response->getContent());

        return $response->json('result.structuredContent');
    }

    /**
     * @param  array<string, mixed>  $result
     * @return list<string>
     */
    private function titles(array $result): array
    {
        return array_column(array_column($result['data'], 'attributes'), 'title');
    }

    /**
     * A cache hit only reads the newest timestamp for the key, never the posts themselves.
     */
    private function assertServedFromTheCache(int $calls): void
    {
        $selects = $this->selectsAgainst(Post::class);

        $this->assertCount($calls, $selects, 'unexpected selects: '.json_encode($selects, JSON_PRETTY_PRINT));

        foreach ($selects as $select) {
            $this->assertStringContainsString('max("updated_at")', $select);
        }
    }
}
