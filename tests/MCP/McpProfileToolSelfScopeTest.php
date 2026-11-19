<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ProfileTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\UserProfile;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Illuminate\Http\Request;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Request as McpToolRequest;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The profile tool returns the authenticated user and nobody else, whatever the caller sends.
 */
class McpProfileToolSelfScopeTest extends IntegrationTestCase
{
    private const ENDPOINT = 'mcp-profile-self-scope';

    private User $me;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        $this->other = User::factory()->create(['name' => 'Other Person', 'email' => 'other@example.com']);
        $this->me = User::factory()->create(['name' => 'Current Person', 'email' => 'me@example.com']);
        User::factory()->count(3)->create();

        $this->mockPosts($this->me->getKey(), 2);
        $this->mockPosts($this->other->getKey(), 3);

        $this->authenticate($this->me);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function it_returns_the_authenticated_user_in_the_index_envelope(): void
    {
        $result = $this->callProfileTool();

        $this->assertOnlyTheCaller($result);
        $this->assertSame('Current Person', $result['data'][0]['attributes']['name']);
        $this->assertSame('me@example.com', $result['data'][0]['attributes']['email']);
        $this->assertSame('users', $result['data'][0]['type']);
        $this->assertSame(1, $result['meta']['current_page']);
        $this->assertSame(1, $result['meta']['last_page']);
        $this->assertArrayHasKey('links', $result);
        $this->assertArrayNotHasKey('relationships', $result['data'][0]);
    }

    /**
     * @param  Closure(User, User): array<string, mixed>  $arguments
     */
    #[Test]
    #[DataProvider('widening_arguments')]
    public function no_argument_selects_another_row(Closure $arguments): void
    {
        $this->assertOnlyTheCaller(
            $this->callProfileTool($arguments($this->me, $this->other))
        );
    }

    /**
     * @return array<string, array{Closure(User, User): array<string, mixed>}>
     */
    public static function widening_arguments(): array
    {
        return [
            'the caller id, negated' => [fn (User $me, User $other): array => ['-id' => $me->getKey()]],
            'another id, negated' => [fn (User $me, User $other): array => ['-id' => $other->getKey()]],
            'another id' => [fn (User $me, User $other): array => ['id' => $other->getKey()]],
            'a list of ids' => [fn (User $me, User $other): array => ['id' => [$other->getKey(), $me->getKey()]]],
            'a comma separated list of ids' => [fn (User $me, User $other): array => ['id' => $other->getKey().','.$me->getKey()]],
            'the caller id negated under filter' => [fn (User $me, User $other): array => ['filter' => ['-id' => $me->getKey()]]],
            'another id under filter' => [fn (User $me, User $other): array => ['filter' => ['id' => $other->getKey()]]],
            'another match, negated' => [fn (User $me, User $other): array => ['-email' => $me->email]],
            'another match' => [fn (User $me, User $other): array => ['email' => $other->email]],
            'a search' => [fn (User $me, User $other): array => ['search' => 'Other']],
            'a sort' => [fn (User $me, User $other): array => ['sort' => '-name']],
            'a later page' => [fn (User $me, User $other): array => ['page' => 2]],
            'a page size' => [fn (User $me, User $other): array => ['page' => 2, 'perPage' => 1]],
            'advanced filters' => [fn (User $me, User $other): array => ['filters' => base64_encode((string) json_encode([
                ['key' => 'matches', 'value' => ['id' => $other->getKey()]],
            ]))]],
            'a relationship and the caller id, negated' => [fn (User $me, User $other): array => ['include' => 'posts', '-id' => $me->getKey()]],
            'every one of them' => [fn (User $me, User $other): array => [
                'id' => $other->getKey(),
                '-id' => $me->getKey(),
                'filter' => ['-id' => $me->getKey()],
                'search' => 'Other',
                'sort' => 'name',
                'page' => 2,
                'perPage' => 1,
            ]],
        ];
    }

    #[Test]
    public function the_url_of_the_mcp_endpoint_does_not_select_another_row(): void
    {
        $this->assertOnlyTheCaller(
            $this->callProfileTool(query: '?-id='.$this->me->getKey())
        );

        $this->assertOnlyTheCaller(
            $this->callProfileTool(query: '?filter[-id]='.$this->me->getKey().'&search=Other&page=2&perPage=1')
        );
    }

    #[Test]
    public function it_does_not_depend_on_the_repository_declaring_the_key_as_matchable(): void
    {
        $this->assertOnlyTheCaller(
            $this->callProfileTool(repository: McpProfileUnmatchableUserRepository::class)
        );

        $this->assertOnlyTheCaller(
            $this->callProfileTool(['-id' => $this->me->getKey()], repository: McpProfileUnmatchableUserRepository::class)
        );
    }

    #[Test]
    public function the_wrapper_execute_operation_is_scoped_too(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);

        Restify::repositories([McpProfileUserRepository::class]);
        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $response = $this->postJson('/'.self::ENDPOINT, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'execute-operation',
                'arguments' => [
                    'repository' => 'users',
                    'operation_type' => 'profile',
                    'parameters' => ['-id' => $this->me->getKey(), 'page' => 2],
                ],
            ],
        ])->assertOk();

        $this->assertFalse($response->json('result.isError'));
        $this->assertOnlyTheCaller($response->json('result.structuredContent'));
    }

    #[Test]
    #[DataProvider('relationship_arguments')]
    public function the_relationships_of_the_caller_are_still_included(string $argument): void
    {
        $result = $this->callProfileTool([$argument => 'posts']);

        $this->assertOnlyTheCaller($result);

        $posts = $result['data'][0]['relationships']['posts'];

        $this->assertCount(2, $posts);
        $this->assertSame([$this->me->getKey()], array_values(array_unique(array_column($posts, 'user_id'))));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function relationship_arguments(): array
    {
        return [
            'include' => ['include'],
            'related' => ['related'],
        ];
    }

    #[Test]
    public function a_relationship_named_on_the_url_of_the_mcp_endpoint_is_still_included(): void
    {
        $result = $this->callProfileTool(query: '?include=posts');

        $this->assertOnlyTheCaller($result);
        $this->assertCount(2, $result['data'][0]['relationships']['posts']);
    }

    #[Test]
    public function it_returns_nothing_when_the_repository_scope_hides_the_caller(): void
    {
        McpProfileUserRepository::$hiddenFromIndex = $this->me->getKey();

        $result = $this->callProfileTool(['-id' => $this->me->getKey()]);

        $this->assertSame([], $result['data']);
        $this->assertSame(0, $result['meta']['total']);
    }

    #[Test]
    public function a_repository_scope_ending_in_an_or_does_not_select_another_row(): void
    {
        McpProfileUserRepository::$indexQueryEndsInAnOr = true;

        $this->assertOnlyTheCaller($this->callProfileTool());
        $this->assertOnlyTheCaller($this->callProfileTool(['-id' => $this->me->getKey()]));
    }

    #[Test]
    public function it_returns_nothing_when_the_show_policy_denies_the_caller(): void
    {
        $_SERVER['restify.users.show'] = false;

        $this->assertSame([], $this->callProfileTool()['data']);
    }

    #[Test]
    public function it_is_not_served_from_the_index_cache_and_does_not_fill_it(): void
    {
        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->enableRepositoryCache();

        Restify::repositories([McpProfileListableUserRepository::class]);

        $index = fn (): array => (new IndexTool(McpProfileListableUserRepository::class))
            ->handle(new McpToolRequest([]))
            ->getStructuredContent();

        $profile = fn (): array => (new ProfileTool(McpProfileListableUserRepository::class))
            ->handle(new McpToolRequest([]))
            ->getStructuredContent();

        $this->assertOnlyTheCaller($profile());
        $this->assertCount(5, $index()['data']);
        $this->assertOnlyTheCaller($profile());
        $this->assertCount(5, $index()['data']);
    }

    #[Test]
    public function it_reports_an_unauthenticated_caller(): void
    {
        $this->logout();

        Restify::repositories([McpProfileUserRepository::class]);

        $response = (new ProfileTool(McpProfileUserRepository::class))->handle(new McpToolRequest([]));

        $this->assertTrue($response->isError());
        $this->assertStringContainsString('UNAUTHENTICATED', (string) $response->content());
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @param  class-string<Repository>  $repository
     * @return array<string, mixed>
     */
    private function callProfileTool(
        array $arguments = [],
        string $query = '',
        string $repository = McpProfileUserRepository::class,
    ): array {
        Restify::repositories([$repository]);
        Mcp::web(self::ENDPOINT, RestifyServer::class);

        $response = $this->postJson('/'.self::ENDPOINT.$query, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'users-profile-tool',
                'arguments' => (object) $arguments,
            ],
        ])->assertOk();

        $this->assertFalse($response->json('result.isError'), (string) $response->getContent());

        return $response->json('result.structuredContent');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function assertOnlyTheCaller(array $result): void
    {
        $this->assertSame(
            [(string) $this->me->getKey()],
            array_column($result['data'], 'id'),
            'The profile tool must return the authenticated user and nobody else.',
        );
        $this->assertSame(1, $result['meta']['total']);
    }
}

class McpProfileUserRepository extends Repository
{
    use HasMcpTools;
    use UserProfile;

    public static string $model = User::class;

    public static string $uriKey = 'users';

    public static int|string|null $hiddenFromIndex = null;

    public static bool $indexQueryEndsInAnOr = false;

    public static array $match = [
        'id' => 'int',
        'email' => 'string',
    ];

    public static array $search = ['name', 'email'];

    public static array $sort = ['id', 'name'];

    public static array $related = ['posts'];

    public static function canUseForProfile(Request $request): bool
    {
        return true;
    }

    public function mcpAllowsIndex(): bool
    {
        return false;
    }

    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query
            ->when(static::$hiddenFromIndex !== null, fn ($query) => $query->whereKeyNot(static::$hiddenFromIndex))
            ->when(static::$indexQueryEndsInAnOr, fn ($query) => $query->where('active', true)->orWhereNotNull('email'));
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('name'),
            Field::make('email'),
        ];
    }
}

class McpProfileUnmatchableUserRepository extends McpProfileUserRepository
{
    public static array $match = [];
}

class McpProfileListableUserRepository extends McpProfileUserRepository
{
    public function mcpAllowsIndex(): bool
    {
        return true;
    }
}
