<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\MCP;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\McpServiceProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * An MCP tool call has no route naming a repository, so each operation tool binds its repository on the request
 * it builds. The getter, action and update tools used to pass it as an input key next to the tool arguments, and
 * the other tools passed nothing: `$request->repository()` there read whatever the client sent under that key.
 * These pin that every tool's request resolves the tool's own repository, whatever the arguments say.
 */
class McpToolRepositoryBindingTest extends IntegrationTestCase
{
    private const OTHER_REPOSITORY = ['mcp_repository_key' => 'users'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['restify.mcp.mode' => 'direct']);

        Restify::repositories([McpBoundPostRepository::class]);

        Mcp::web('mcp-bound', RestifyServer::class);
    }

    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            McpServiceProvider::class,
        ]);
    }

    #[Test]
    public function the_index_tool_resolves_its_own_repository(): void
    {
        Post::factory()->count(2)->create(['user_id' => null]);
        User::factory()->create(['email' => 'member@example.com']);

        $response = $this->callTool('mcp-bound-posts-index-tool', self::OTHER_REPOSITORY);

        $this->assertSame(['posts', 'posts'], array_column($this->toolResult($response)['data'], 'type'));
        $this->assertResolvedOnlyTheBoundRepository();
        $response->assertDontSee('member@example.com');
    }

    #[Test]
    public function the_show_tool_resolves_its_own_repository(): void
    {
        $post = $this->postSharingItsKeyWithAUser();

        $response = $this->callTool('mcp-bound-posts-show-tool', ['id' => (string) $post->getKey()] + self::OTHER_REPOSITORY);

        $this->assertSame('posts', $this->toolResult($response)['type']);
        $this->assertSame('Original title', $this->toolResult($response)['attributes']['title']);
        $this->assertResolvedOnlyTheBoundRepository();
        $response->assertDontSee('member@example.com');
    }

    #[Test]
    public function the_store_tool_resolves_its_own_repository(): void
    {
        $response = $this->callTool('mcp-bound-posts-store-tool', ['title' => 'Stored via MCP'] + self::OTHER_REPOSITORY);

        $this->assertSame('posts', $this->toolResult($response)['data']['type']);
        $this->assertResolvedOnlyTheBoundRepository();
        $this->assertDatabaseHas(Post::class, ['title' => 'Stored via MCP']);
        $this->assertDatabaseCount(User::class, 0);
    }

    #[Test]
    public function the_update_tool_resolves_its_own_repository(): void
    {
        $post = $this->postSharingItsKeyWithAUser();

        $response = $this->callTool('mcp-bound-posts-update-tool', [
            'id' => (string) $post->getKey(),
            'title' => 'Updated via MCP',
            'name' => 'Updated member',
        ] + self::OTHER_REPOSITORY);

        $this->assertSame('posts', $this->toolResult($response)['data']['type']);
        $this->assertResolvedOnlyTheBoundRepository();
        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Updated via MCP']);
        $this->assertDatabaseHas(User::class, ['id' => $post->getKey(), 'name' => 'Original name']);
    }

    #[Test]
    public function the_delete_tool_resolves_its_own_repository(): void
    {
        $post = $this->postSharingItsKeyWithAUser();

        $response = $this->callTool('mcp-bound-posts-delete-tool', ['id' => (string) $post->getKey()] + self::OTHER_REPOSITORY);

        $this->assertTrue($this->toolResult($response)['deleted']);
        $this->assertResolvedOnlyTheBoundRepository();
        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
        $this->assertDatabaseHas(User::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function the_getter_tool_resolves_its_own_repository(): void
    {
        $response = $this->callTool('mcp-bound-posts-mcp-bound-repository-getter-tool', self::OTHER_REPOSITORY);

        $this->assertSame('mcp-bound-posts', $this->toolResult($response)['result']['repository']);
    }

    #[Test]
    public function the_action_tool_resolves_its_own_repository(): void
    {
        $response = $this->callTool('mcp-bound-posts-mcp-bound-repository-action-tool', self::OTHER_REPOSITORY);

        $this->assertSame('mcp-bound-posts', $this->toolResult($response)['result']['repository']);
    }

    #[Test]
    public function the_profile_tool_resolves_its_own_repository(): void
    {
        // The profile tool belongs to the repository keyed `users`, so an MCP-enabled one takes that key here.
        Restify::$repositories = [];
        Restify::repositories([McpBoundUserRepository::class, McpBoundPostRepository::class]);

        Post::factory()->create(['user_id' => null]);
        // A stored user: the profile is the row of the authenticated model, and the default mocked user has none.
        $this->authenticate(User::factory()->create(['email' => 'member@example.com']));
        User::factory()->create(['email' => 'other@example.com']);

        $response = $this->callTool('users-profile-tool', ['mcp_repository_key' => 'mcp-bound-posts']);

        $this->assertSame(['member@example.com'], array_column(array_column($this->toolResult($response)['data'], 'attributes'), 'email'));
        $this->assertResolvedOnlyTheBoundRepository('users');
    }

    #[Test]
    public function the_wrapper_execute_operation_tool_resolves_the_repository_it_was_asked_for(): void
    {
        config(['restify.mcp.mode' => 'wrapper']);

        Post::factory()->count(2)->create(['user_id' => null]);
        User::factory()->create(['email' => 'member@example.com']);

        $response = $this->callTool('execute-operation', [
            'repository' => 'mcp-bound-posts',
            'operation_type' => 'index',
            'parameters' => self::OTHER_REPOSITORY,
        ]);

        $this->assertSame(['posts', 'posts'], array_column($this->toolResult($response)['data'], 'type'));
        $this->assertResolvedOnlyTheBoundRepository();
        $response->assertDontSee('member@example.com');
    }

    #[Test]
    public function a_tool_resolves_its_repository_without_any_repository_argument(): void
    {
        Post::factory()->count(2)->create(['user_id' => null]);

        $response = $this->callTool('mcp-bound-posts-index-tool', []);

        $this->assertCount(2, $this->toolResult($response)['data']);
        $this->assertResolvedOnlyTheBoundRepository();
    }

    #[Test]
    public function a_tool_resolves_its_own_repository_whatever_the_endpoint_query_string_names(): void
    {
        $post = $this->postSharingItsKeyWithAUser();

        $response = $this->callTool('mcp-bound-posts-delete-tool', ['id' => (string) $post->getKey()], '/mcp-bound?next=/api/restify/users');

        $this->assertTrue($this->toolResult($response)['deleted']);
        $this->assertResolvedOnlyTheBoundRepository();
        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
        $this->assertDatabaseHas(User::class, ['id' => $post->getKey()]);
    }

    private function postSharingItsKeyWithAUser(): Post
    {
        $post = Post::factory()->create(['user_id' => null, 'title' => 'Original title']);

        User::factory()->create(['id' => $post->getKey(), 'name' => 'Original name', 'email' => 'member@example.com']);

        return $post;
    }

    private function assertResolvedOnlyTheBoundRepository(string $uriKey = 'mcp-bound-posts'): void
    {
        $this->assertNotEmpty($_SERVER['McpBoundPostRepository.resolved'] ?? []);
        $this->assertSame([$uriKey], array_values(array_unique($_SERVER['McpBoundPostRepository.resolved'])));
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function callTool(string $name, array $arguments, string $endpoint = '/mcp-bound'): TestResponse
    {
        return $this->postJson($endpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => $arguments,
            ],
        ])->assertOk()->assertJsonMissingPath('error')->assertJsonPath('result.isError', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function toolResult(TestResponse $response): array
    {
        return json_decode($response->json('result.content.0.text'), true, flags: JSON_THROW_ON_ERROR);
    }
}

class McpBoundPostRepository extends Repository
{
    use HasMcpTools;

    public static $model = Post::class;

    public static string $uriKey = 'mcp-bound-posts';

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
        ];
    }

    public static function mainQuery(RestifyRequest $request, $query)
    {
        static::recordRepositoryOf($request);

        return $query;
    }

    public function store(RestifyRequest $request)
    {
        static::recordRepositoryOf($request);

        return parent::store($request);
    }

    public static function recordRepositoryOf(RestifyRequest $request): string
    {
        return $_SERVER['McpBoundPostRepository.resolved'][] = $request->repository()::uriKey();
    }

    public function getters(RestifyRequest $request): array
    {
        return [
            new McpBoundRepositoryGetter,
        ];
    }

    public function actions(RestifyRequest $request): array
    {
        return [
            (new McpBoundRepositoryAction)->standalone(),
        ];
    }

    public function mcpAllowsShow(): bool
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

    public function mcpAllowsActions(): bool
    {
        return true;
    }

    public function mcpAllowsGetters(): bool
    {
        return true;
    }
}

class McpBoundUserRepository extends Repository
{
    use HasMcpTools;

    public static $model = User::class;

    public static string $uriKey = 'users';

    public static array $match = [
        'id' => 'int',
    ];

    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('email'),
        ];
    }

    public static function mainQuery(RestifyRequest $request, $query)
    {
        McpBoundPostRepository::recordRepositoryOf($request);

        return $query;
    }
}

class McpBoundRepositoryGetter extends Getter
{
    public static $uriKey = 'mcp-bound-repository';

    public function handle(GetterRequest $request): JsonResponse
    {
        return response()->json(['repository' => McpBoundPostRepository::recordRepositoryOf($request)]);
    }
}

class McpBoundRepositoryAction extends Action
{
    public static $uriKey = 'mcp-bound-repository';

    public function handle(ActionRequest $request): JsonResponse
    {
        return response()->json(['repository' => McpBoundPostRepository::recordRepositoryOf($request)]);
    }
}
