<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

class McpGlobalSearchAuthorizationTest extends IntegrationTestCase
{
    private const ENDPOINT = 'mcp-global-search-authorization';

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        Gate::policy(Post::class, PostPolicy::class);

        Restify::repositories([McpGlobalSearchPostRepository::class]);

        Mcp::web(self::ENDPOINT, RestifyServer::class);

        McpGlobalSearchPostRepository::$hiddenId = null;
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function global_search_skips_rows_the_index_query_hides(): void
    {
        $hidden = Post::factory()->create(['title' => 'Hidden post']);
        $visible = Post::factory()->create(['title' => 'Visible post']);

        McpGlobalSearchPostRepository::$hiddenId = $hidden->getKey();

        $this->assertSame([$visible->getKey()], $this->searchIds('post'));
    }

    #[Test]
    public function global_search_skips_rows_the_user_cannot_show(): void
    {
        $hidden = Post::factory()->create(['title' => 'Hidden post']);
        $visible = Post::factory()->create(['title' => 'Visible post']);

        $_SERVER['restify.post.show.callback'] = fn ($user, Post $post) => $post->isNot($hidden);

        $this->assertSame([$visible->getKey()], $this->searchIds('post'));
    }

    /**
     * @return list<int|string>
     */
    private function searchIds(string $search): array
    {
        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'global-search',
                'arguments' => ['search' => $search],
            ],
        ])->assertOk();

        $content = json_decode($response->json('result.content.0.text'), true);

        $this->assertSame(\count($content['results']), $content['total']);

        $results = array_filter(
            $content['results'],
            static fn (array $result): bool => $result['repositoryName'] === McpGlobalSearchPostRepository::$uriKey,
        );

        return array_values(array_column($results, 'repositoryId'));
    }
}

class McpGlobalSearchPostRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-global-search-posts';

    public static array $search = ['title'];

    public static int|string|null $hiddenId = null;

    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query->whereKeyNot(static::$hiddenId);
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
        ];
    }
}
