<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\MCP\Tools\Operations\ShowTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Role\Role;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Arr;
use Laravel\Mcp\Request as McpToolRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatedDepthLimitTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    /**
     * Six levels, each declared by the repository it is requested on.
     */
    private const SIX_LEVELS = 'posts.comments.parent.user.creator.roles';

    private const SIX_LEVELS_IN_RESPONSE = 'relationships.posts.0.relationships.comments.0.relationships.parent'
        .'.relationships.user.relationships.creator.relationships.roles.0';

    private const FIVE_LEVELS_IN_RESPONSE = 'relationships.posts.0.relationships.comments.0.relationships.parent'
        .'.relationships.user.relationships.creator';

    protected function setUp(): void
    {
        parent::setUp();

        Restify::repositories([
            DepthUserRepository::class,
            DepthPostRepository::class,
            DepthCommentRepository::class,
            DepthRoleRepository::class,
        ]);
    }

    #[Test]
    #[TestWith([5, 'a.b.c.d.e'], 'the shipped default')]
    #[TestWith([null, 'a.b.c.d.e'], 'null: the package default')]
    #[TestWith([2, 'a.b'], 'lowered in config')]
    #[TestWith([1, 'a'], 'one level')]
    #[TestWith(['6', 'a.b.c.d.e.f'], 'raised through env')]
    #[TestWith([7, 'a.b.c.d.e.f.g'], 'raised to the requested depth')]
    #[TestWith([PHP_INT_MAX, 'a.b.c.d.e.f.g'], 'raised as far as an int goes')]
    #[TestWith([0, 'a.b.c.d.e.f.g'], 'turned off with 0')]
    #[TestWith([false, 'a.b.c.d.e.f.g'], 'turned off with false')]
    public function a_requested_path_is_cut_to_the_limit(int|string|false|null $maxDepth, string $deepest): void
    {
        config(['restify.related.max_depth' => $maxDepth]);

        $tree = $this->treeFor(['related' => 'a.b.c.d.e.f.g']);

        $this->assertSame($deepest, end($tree));
        $this->assertCount(substr_count($deepest, '.') + 1, $tree);
    }

    #[Test]
    public function the_shipped_config_cuts_a_path_at_five_levels(): void
    {
        $this->assertSame(
            ['a', 'a.b', 'a.b.c', 'a.b.c.d', 'a.b.c.d.e'],
            $this->treeFor(['related' => 'a.b.c.d.e.f.g']),
        );
    }

    #[Test]
    public function the_default_limit_applies_when_the_published_config_predates_the_related_key(): void
    {
        config(['restify' => Arr::except(config('restify'), ['related'])]);

        $tree = $this->treeFor(['related' => 'a.b.c.d.e.f.g']);

        $this->assertSame('a.b.c.d.e', end($tree));
    }

    #[Test]
    public function the_include_parameter_is_cut_the_same_way(): void
    {
        $tree = $this->treeFor(['include' => 'a.b.c.d.e.f.g']);

        $this->assertSame('a.b.c.d.e', end($tree));
    }

    #[Test]
    public function a_path_within_the_limit_is_untouched(): void
    {
        $this->assertSame(
            ['a', 'a.b', 'a.b.c', 'x', 'k', 'k.l', 'k.l.m', 'k.l.m.n', 'k.l.m.n.o'],
            $this->treeFor(['related' => 'a.b.c, x, k.l.m.n.o']),
        );
    }

    #[Test]
    public function every_path_in_the_list_is_cut_on_its_own(): void
    {
        config(['restify.related.max_depth' => 2]);

        $this->assertSame(
            ['a', 'a.b', 'x', 'x.y', 'k'],
            $this->treeFor(['related' => 'a.b.c, x.y.z.w, k']),
        );
    }

    #[Test]
    public function column_selections_survive_the_cut(): void
    {
        config(['restify.related.max_depth' => 2]);

        $related = $this->relatedFor(['related' => 'a[id|name].b[title].c[id]']);

        $this->assertSame(['a', 'a.b'], $related->makeTree());
        $this->assertSame(['id', 'name'], $related->getColumnsFor('users.a'));
        $this->assertSame(['title'], $related->getColumnsFor('users.a.b'));
    }

    #[Test]
    public function a_path_the_repository_declares_is_kept_in_full(): void
    {
        UserRepository::$related = ['a.b.c.d.e.f.g'];

        $declared = $this->treeFor(['related' => 'a.b.c.d.e.f.g']);
        $withColumns = $this->treeFor(['related' => 'a[id].b.c.d.e.f.g[id|name]']);
        $longerThanDeclared = $this->treeFor(['related' => 'a.b.c.d.e.f.g.h.i']);
        $notDeclared = $this->treeFor(['related' => 'x.y.z.w.v.u.t']);

        $this->assertSame('a.b.c.d.e.f.g', end($declared));
        $this->assertSame('a.b.c.d.e.f.g', end($withColumns));
        $this->assertSame('a.b.c.d.e.f.g', end($longerThanDeclared));
        $this->assertSame('x.y.z.w.v', end($notDeclared));
    }

    #[Test]
    public function the_index_response_stops_at_the_default_limit(): void
    {
        $user = $this->seedSixLevels();

        $row = $this->indexRowFor($user, ['related' => self::SIX_LEVELS]);

        $this->assertSame('Creator', Arr::get($row, self::FIVE_LEVELS_IN_RESPONSE.'.attributes.name'));
        $this->assertFalse(Arr::has($row, self::FIVE_LEVELS_IN_RESPONSE.'.relationships'));
    }

    #[Test]
    #[TestWith([6], 'raised in config')]
    #[TestWith([0], 'turned off')]
    public function the_index_response_reaches_the_sixth_level_when_the_limit_allows_it(int $maxDepth): void
    {
        config(['restify.related.max_depth' => $maxDepth]);

        $user = $this->seedSixLevels();

        $row = $this->indexRowFor($user, ['related' => self::SIX_LEVELS]);

        $this->assertSame('roles', Arr::get($row, self::SIX_LEVELS_IN_RESPONSE.'.type'));
    }

    #[Test]
    public function the_index_response_stops_at_a_lowered_limit(): void
    {
        config(['restify.related.max_depth' => 2]);

        $user = $this->seedSixLevels();

        $row = $this->indexRowFor($user, ['include' => self::SIX_LEVELS]);

        $this->assertSame('comments', Arr::get($row, 'relationships.posts.0.relationships.comments.0.type'));
        $this->assertFalse(Arr::has($row, 'relationships.posts.0.relationships.comments.0.relationships'));
    }

    #[Test]
    public function the_show_response_stops_at_the_default_limit(): void
    {
        $user = $this->seedSixLevels();

        $row = $this->getJson(DepthUserRepository::route($user, query: ['related' => self::SIX_LEVELS]))
            ->assertOk()
            ->json('data');

        $this->assertSame('Creator', Arr::get($row, self::FIVE_LEVELS_IN_RESPONSE.'.attributes.name'));
        $this->assertFalse(Arr::has($row, self::FIVE_LEVELS_IN_RESPONSE.'.relationships'));
    }

    #[Test]
    public function a_path_within_the_default_limit_is_served_as_before(): void
    {
        $user = $this->seedSixLevels();

        $row = $this->indexRowFor($user, ['related' => 'posts.comments.parent.user.creator']);

        $this->assertSame('Creator', Arr::get($row, self::FIVE_LEVELS_IN_RESPONSE.'.attributes.name'));
        $this->assertFalse(Arr::has($row, self::FIVE_LEVELS_IN_RESPONSE.'.relationships'));
    }

    /**
     * The singleton RelatedDto is what stops a circular path from being serialized forever; each of
     * its levels is still eager loaded with a query of its own.
     */
    #[Test]
    #[TestWith([5, 3, 2], 'default limit: five levels are loaded')]
    #[TestWith([2, 1, 1], 'lowered limit: two levels are loaded')]
    #[TestWith([0, 6, 5], 'limit turned off: every level is loaded')]
    public function a_circular_path_is_eager_loaded_up_to_the_limit(int $maxDepth, int $postSelects, int $nestedUserSelects): void
    {
        config(['restify.related.max_depth' => $maxDepth]);

        $this->shareRelatedDtoAsAnApplicationDoes();

        $user = User::factory()->create();
        Post::factory(2)->create(['user_id' => $user->id]);

        $this->recordQueries();

        $this->getJson(DepthUserRepository::route(query: [
            'related' => 'posts.user.posts.user.posts.user.posts.user.posts.user.posts',
        ]))->assertOk();

        $this->assertSelectCount($postSelects, Post::class);
        // The count and the page of users come first.
        $this->assertSelectCount(2 + $nestedUserSelects, User::class);
    }

    #[Test]
    public function the_mcp_index_tool_stops_at_the_default_limit(): void
    {
        $this->shareRelatedDtoAsAnApplicationDoes();

        $user = $this->seedSixLevels();

        $response = (new IndexTool(DepthUserRepository::class))->handle(new McpToolRequest([
            'include' => self::SIX_LEVELS,
        ]));

        $row = collect($response->getStructuredContent()['data'])->firstWhere('id', (string) $user->getKey());

        $this->assertSame('Creator', Arr::get($row, self::FIVE_LEVELS_IN_RESPONSE.'.attributes.name'));
        $this->assertFalse(Arr::has($row, self::FIVE_LEVELS_IN_RESPONSE.'.relationships'));
    }

    #[Test]
    public function the_mcp_index_tool_reaches_the_sixth_level_when_the_limit_is_raised(): void
    {
        config(['restify.related.max_depth' => 6]);

        $this->shareRelatedDtoAsAnApplicationDoes();

        $user = $this->seedSixLevels();

        $response = (new IndexTool(DepthUserRepository::class))->handle(new McpToolRequest([
            'include' => self::SIX_LEVELS,
        ]));

        $row = collect($response->getStructuredContent()['data'])->firstWhere('id', (string) $user->getKey());

        $this->assertSame('roles', Arr::get($row, self::SIX_LEVELS_IN_RESPONSE.'.type'));
    }

    #[Test]
    #[TestWith([5, 'posts.comments.parent.user.creator'], 'default limit')]
    #[TestWith([6, 'posts.comments.parent.user.creator.roles'], 'raised in config')]
    public function the_mcp_show_tool_parses_include_through_the_same_limit(int $maxDepth, string $deepest): void
    {
        config(['restify.related.max_depth' => $maxDepth]);

        $this->shareRelatedDtoAsAnApplicationDoes();

        $user = $this->seedSixLevels();

        $response = (new ShowTool(DepthUserRepository::class))->handle(new McpToolRequest([
            'id' => (string) $user->getKey(),
            'include' => self::SIX_LEVELS,
        ]));

        $tree = array_values($this->app->make(RelatedDto::class)->makeTree());

        $this->assertSame((string) $user->getKey(), $response->getStructuredContent()['id']);
        $this->assertSame($deepest, end($tree));
    }

    #[Test]
    #[TestWith([5, 'at most 5 levels deep, deeper levels are ignored'], 'default limit')]
    #[TestWith([3, 'at most 3 levels deep, deeper levels are ignored'], 'lowered limit')]
    #[TestWith([0, 'supports unlimited nesting depth'], 'limit turned off')]
    public function the_mcp_include_description_states_the_limit(int $maxDepth, string $expected): void
    {
        config(['restify.related.max_depth' => $maxDepth]);

        $schema = (new IndexTool(DepthUserRepository::class))->toArray()['inputSchema'];

        $this->assertStringContainsString($expected, $schema['properties']['include']['description']);
    }

    /**
     * Outside the test environment the RelatedDto is a singleton: one request parses `related`
     * once and every nested repository reads that same instance.
     */
    private function shareRelatedDtoAsAnApplicationDoes(): void
    {
        $this->app->singleton(RelatedDto::class, fn (): RelatedDto => new RelatedDto);
    }

    /**
     * @param  array<string, string>  $query
     * @return array<int, string>
     */
    private function treeFor(array $query): array
    {
        return array_values($this->relatedFor($query)->makeTree());
    }

    /**
     * @param  array<string, string>  $query
     */
    private function relatedFor(array $query): RelatedDto
    {
        return (new RelatedDto)->sync(
            RestifyRequest::create('/', 'GET', $query),
            UserRepository::resolveWith(new User),
        );
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>
     */
    private function indexRowFor(User $user, array $query): array
    {
        $rows = $this->getJson(DepthUserRepository::route(query: $query))->assertOk()->json('data');

        return collect($rows)->firstWhere('id', (string) $user->getKey());
    }

    /**
     * user > posts > comments > parent > user > creator > roles
     */
    private function seedSixLevels(): User
    {
        $creator = User::factory()->create(['name' => 'Creator']);
        $creator->roles()->attach(Role::factory()->create());

        $author = User::factory()->create(['creator_id' => $creator->id]);
        $parent = Comment::factory()->create([
            'post_id' => Post::factory()->create(['user_id' => $author->id])->id,
            'user_id' => $author->id,
        ]);

        $user = User::factory()->create();

        Comment::factory()->create([
            'post_id' => Post::factory()->create(['user_id' => $user->id])->id,
            'user_id' => $user->id,
            'parent_comment_id' => $parent->id,
        ]);

        return $user;
    }
}

class DepthUserRepository extends Repository
{
    use HasMcpTools;

    public static string $model = User::class;

    public static string $uriKey = 'depth-users';

    public static function include(): array
    {
        return [
            'posts' => HasMany::make('posts', DepthPostRepository::class),
            'creator' => BelongsTo::make('creator', DepthUserRepository::class),
            'roles' => MorphToMany::make('roles', DepthRoleRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}

class DepthPostRepository extends Repository
{
    use HasMcpTools;

    public static string $model = Post::class;

    public static string $uriKey = 'depth-posts';

    public static function include(): array
    {
        return [
            'comments' => HasMany::make('comments', DepthCommentRepository::class),
            'user' => BelongsTo::make('user', DepthUserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [field('title')];
    }
}

class DepthCommentRepository extends Repository
{
    use HasMcpTools;

    public static string $model = Comment::class;

    public static string $uriKey = 'depth-comments';

    public static function include(): array
    {
        return [
            'parent' => BelongsTo::make('parent', DepthCommentRepository::class),
            'user' => BelongsTo::make('user', DepthUserRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [field('comment')];
    }
}

class DepthRoleRepository extends Repository
{
    use HasMcpTools;

    public static string $model = Role::class;

    public static string $uriKey = 'depth-roles';

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}
