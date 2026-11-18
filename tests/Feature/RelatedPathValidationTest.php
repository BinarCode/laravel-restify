<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;
use Binaryk\LaravelRestify\MCP\Tools\Operations\IndexTool;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsFilteredQueryGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToRelation;
use Illuminate\Database\Eloquent\Relations\HasMany as HasManyRelation;
use Illuminate\Testing\TestResponse;
use Laravel\Mcp\Request as McpToolRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * A requested `related` / `include` path is only ever compared with what the repositories declare. A segment
 * nobody declared is not called on the model, not eager loaded and not serialized.
 */
class RelatedPathValidationTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    /**
     * @var array<class-string<Model>, int>
     */
    private array $seeded;

    protected function setUp(): void
    {
        parent::setUp();

        $editor = User::factory()->create(['name' => 'The editor']);

        Company::factory()->create()->users()->attach($editor->id, ['is_admin' => true]);

        foreach (User::factory(2)->create() as $user) {
            foreach (Post::factory(2)->create(['user_id' => $user->id, 'edited_by' => $editor->id]) as $post) {
                Comment::factory(2)->create(['post_id' => $post->id, 'user_id' => $user->id]);
            }
        }

        $this->seeded = $this->rowCounts();

        $this->recordQueries();
    }

    protected function tearDown(): void
    {
        // Guarding is process wide: put it back, so a failure here cannot leak into the tests that follow.
        Model::reguard();

        parent::tearDown();
    }

    #[Test]
    #[TestWith(['related', 'posts.truncate'], 'related')]
    #[TestWith(['include', 'posts.truncate'], 'include')]
    #[TestWith(['related', 'posts[title].truncate'], 'related with columns')]
    #[TestWith(['include', 'posts[title|description].truncate'], 'include with columns')]
    #[TestWith(['related', 'posts.unguard'], 'related, a static of the model')]
    #[TestWith(['include', 'posts[title].unguard'], 'include with columns, a static of the model')]
    #[TestWith(['related', 'posts.comments.truncate'], 'past a real relation')]
    #[TestWith(['related', 'posts.editor.truncate'], 'past another real relation')]
    #[TestWith(['related', 'posts, posts.truncate'], 'beside the declared relation')]
    public function a_segment_under_a_string_declaration_is_never_called(string $parameter, string $requested): void
    {
        UserRepository::$related = ['posts'];

        $this->getJson(UserRepository::route(query: [$parameter => $requested]))->assertOk();

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['posts.truncate'])]
    #[TestWith(['posts.unguard'])]
    #[TestWith(['posts[title].comments[comment].truncate'])]
    #[TestWith(['posts.comments.unguard'])]
    public function a_segment_under_an_eager_field_is_never_called(string $requested): void
    {
        UserRepository::$related = [HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = [HasMany::make('comments', CommentRepository::class)];

        $this->getJson(UserRepository::route(query: ['related' => $requested]))->assertOk();

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['posts.comments.truncate'])]
    #[TestWith(['posts.comments.unguard'])]
    #[TestWith(['posts.comments, posts.truncate'])]
    public function a_segment_past_a_dotted_declaration_is_never_called(string $requested): void
    {
        UserRepository::$related = ['posts.comments'];

        $this->getJson(UserRepository::route(query: ['related' => $requested]))->assertOk();

        $this->assertNothingRan();
    }

    #[Test]
    public function an_undeclared_relation_method_is_never_called(): void
    {
        Restify::repositories([ProbedUserRepository::class]);

        $response = $this->getJson(ProbedUserRepository::route(query: ['related' => 'posts.probe']))->assertOk();

        $this->assertSame([], ProbedPost::$called);
        $this->assertArrayNotHasKey('probe', $this->relatedOfTheFirstRowWith($response, 'posts')[0]);
    }

    #[Test]
    public function a_declared_name_the_model_does_not_define_is_not_forwarded_to_the_query_builder(): void
    {
        Restify::repositories([ProbedUserRepository::class]);

        ProbedUserRepository::$related = ['posts', 'posts.forwarded'];

        // No row matches, so nothing is serialized: only the validation of the path could make the call.
        $this->getJson(ProbedUserRepository::route(query: ['related' => 'posts.forwarded', 'id' => 0]))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertSame([], ProbedPost::$called);
    }

    #[Test]
    #[TestWith(['related', 'posts.truncate'])]
    #[TestWith(['include', 'posts.truncate'])]
    #[TestWith(['related', 'posts[title].unguard'])]
    #[TestWith(['include', 'posts.unguard'])]
    public function global_search_never_calls_a_segment(string $parameter, string $requested): void
    {
        UserRepository::$related = ['posts'];

        $this->getJson(Restify::path('search', ['search' => 'a', $parameter => $requested]))->assertOk();

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['include', 'posts.truncate'])]
    #[TestWith(['related', 'posts.truncate'])]
    #[TestWith(['include', 'posts[title].unguard'])]
    public function the_mcp_index_tool_never_calls_a_segment(string $argument, string $requested): void
    {
        Restify::repositories([McpUserWithPostsRepository::class]);

        $result = (new IndexTool(McpUserWithPostsRepository::class))
            ->handle(new McpToolRequest([$argument => $requested]))
            ->getStructuredContent();

        $this->assertCount($this->seeded[User::class], $result['data']);
        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['comments.truncate'])]
    #[TestWith(['comments[comment].unguard'])]
    public function a_nested_index_never_calls_a_segment(string $requested): void
    {
        UserRepository::$related = ['posts' => HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = ['comments'];

        $user = Post::query()->firstOrFail()->user;

        $this->getJson(UserRepository::route("{$user->id}/posts", ['related' => $requested]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['related', 'comments.truncate'])]
    #[TestWith(['include', 'comments.unguard'])]
    public function a_getter_query_never_calls_a_segment(string $parameter, string $requested): void
    {
        PostRepository::$related = ['comments'];

        $this->getJson(PostRepository::getter(PostsFilteredQueryGetter::class)."?{$parameter}={$requested}")
            ->assertOk()
            ->assertJsonPath('count', $this->seeded[Post::class]);

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['related', 'comments.truncate'])]
    #[TestWith(['include', 'comments.unguard'])]
    public function an_action_query_never_calls_a_segment(string $parameter, string $requested): void
    {
        PostRepository::$related = ['comments'];

        $this->postJson(
            PostRepository::route(query: [$parameter => $requested], action: new PublishPostAction),
            ['repositories' => 'all'],
        )->assertOk();

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['related', 'posts.truncate'])]
    #[TestWith(['include', 'posts[title].unguard'])]
    public function a_show_request_never_calls_a_segment(string $parameter, string $requested): void
    {
        UserRepository::$related = ['posts'];

        $user = Post::query()->firstOrFail()->user;

        $this->getJson(UserRepository::route($user, [$parameter => $requested]))
            ->assertOk()
            ->assertJsonCount(2, 'data.relationships.posts');

        $this->assertNothingRan();
    }

    #[Test]
    #[TestWith(['related', 'posts.editor.companies'])]
    #[TestWith(['include', 'posts.editor.companies'])]
    #[TestWith(['related', 'posts[title].editor[name].companies'])]
    #[TestWith(['related', 'posts, posts.editor'])]
    public function a_relation_under_a_string_declaration_is_neither_loaded_nor_serialized(string $parameter, string $requested): void
    {
        UserRepository::$related = ['posts'];

        $response = $this->getJson(UserRepository::route(query: [$parameter => $requested]))->assertOk();

        $posts = $this->relatedOfTheFirstRowWith($response, 'posts');

        $this->assertCount(2, $posts);

        foreach ($posts as $post) {
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayNotHasKey('editor', $post);
        }

        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(0, Company::class);
        $this->assertNoRelatedUserSelects();
    }

    #[Test]
    public function a_relation_past_a_dotted_declaration_is_neither_loaded_nor_serialized(): void
    {
        UserRepository::$related = ['posts.comments'];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments.user, posts.editor']))
            ->assertOk();

        $posts = $this->relatedOfTheFirstRowWith($response, 'posts.comments');

        $this->assertCount(2, $posts);

        foreach ($posts as $post) {
            $this->assertArrayNotHasKey('editor', $post);
            $this->assertCount(2, $post['comments']);

            foreach ($post['comments'] as $comment) {
                $this->assertArrayNotHasKey('user', $comment);
            }
        }

        $this->assertSelectCount(1, Comment::class);
        $this->assertNoRelatedUserSelects();
    }

    #[Test]
    public function a_relation_the_nested_repository_does_not_declare_is_not_loaded(): void
    {
        UserRepository::$related = [HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = [];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments, posts.editor']))
            ->assertOk();

        $posts = $this->relatedOfTheFirstRowWith($response, 'posts');

        $this->assertSame('posts', $posts[0]['type']);
        $this->assertArrayNotHasKey('relationships', $posts[0]);
        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(0, Comment::class);
        $this->assertNoRelatedUserSelects();
    }

    #[Test]
    public function a_relation_the_nested_repository_hides_from_the_user_is_not_loaded(): void
    {
        UserRepository::$related = [HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = [HasMany::make('comments', CommentRepository::class)->canSee(fn (): bool => false)];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments']))->assertOk();

        $this->assertArrayNotHasKey('relationships', $this->relatedOfTheFirstRowWith($response, 'posts')[0]);
        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(0, Comment::class);
    }

    #[Test]
    public function a_string_declaration_of_a_nested_repository_does_not_nest_further(): void
    {
        UserRepository::$related = [HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = ['comments'];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments.user']))->assertOk();

        $comments = $this->relatedOfTheFirstRowWith($response, 'posts')[0]['relationships']['comments'];

        $this->assertCount(2, $comments);
        $this->assertArrayNotHasKey('user', $comments[0]);
        $this->assertSelectCount(1, Comment::class);
        $this->assertNoRelatedUserSelects();
    }

    #[Test]
    #[TestWith(['posts.comments.user, posts.editor'])]
    #[TestWith(['posts[title].comments[comment].user[name], posts.editor[name]'])]
    public function a_path_declared_by_each_nested_repository_is_eager_loaded_and_serialized(string $requested): void
    {
        Restify::repositories([CommentWithUserRepository::class]);

        UserRepository::$related = [HasMany::make('posts', PostRepository::class)];
        PostRepository::$related = [
            HasMany::make('comments', CommentWithUserRepository::class),
            BelongsTo::make('editor', UserRepository::class),
        ];

        $response = $this->getJson(UserRepository::route(query: ['related' => $requested]))->assertOk();

        $post = $this->relatedOfTheFirstRowWith($response, 'posts')[0];

        $this->assertSame('posts', $post['type']);
        $this->assertSame('The editor', $post['relationships']['editor']['attributes']['name']);
        $this->assertCount(2, $post['relationships']['comments']);
        $this->assertSame('users', $post['relationships']['comments'][0]['relationships']['user']['type']);

        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(1, Comment::class);
        $this->assertCount(2, $this->relatedUserSelects(), 'one select for the editors, one for the comment authors');
    }

    #[Test]
    public function an_eager_field_beside_its_dotted_path_loads_both(): void
    {
        UserRepository::$related = [
            'posts' => HasMany::make('posts', PostRepository::class),
            'posts.comments',
        ];

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts.comments']))->assertOk();

        $row = $this->firstRowWith($response, 'posts');

        $this->assertSame('posts', $row['relationships']['posts'][0]['type']);
        $this->assertCount(2, $row['relationships']['posts.comments'][0]['comments']);
        $this->assertSelectCount(1, Post::class);
        $this->assertSelectCount(1, Comment::class);
    }

    #[Test]
    public function a_relation_registered_on_the_model_at_runtime_is_eager_loaded(): void
    {
        Restify::repositories([ProbedUserRepository::class]);

        ProbedUser::resolveRelationUsing(
            'writtenPosts',
            fn (ProbedUser $user): HasManyRelation => $user->hasMany(Post::class, 'user_id'),
        );

        ProbedUserRepository::$related = ['writtenPosts'];

        $response = $this->getJson(ProbedUserRepository::route(query: ['related' => 'writtenPosts']))->assertOk();

        $this->assertCount(2, $this->relatedOfTheFirstRowWith($response, 'writtenPosts'));
        $this->assertSelectCount(1, Post::class);
    }

    #[Test]
    public function a_declared_name_that_is_not_a_relation_is_still_forwarded_to_the_model(): void
    {
        UserRepository::$related = ['profile'];

        $this->getJson(UserRepository::route(query: ['related' => 'profile']))
            ->assertOk()
            ->assertJsonPath('data.0.relationships.profile.roles', ['admin']);
    }

    private function assertNothingRan(): void
    {
        $this->assertFalse(Model::isUnguarded(), 'mass assignment guarding was switched off');
        $this->assertSame($this->seeded, $this->rowCounts());
    }

    /**
     * @return array<class-string<Model>, int>
     */
    private function rowCounts(): array
    {
        return [
            User::class => User::query()->count(),
            Post::class => Post::query()->count(),
            Comment::class => Comment::query()->count(),
            Company::class => Company::query()->count(),
        ];
    }

    /**
     * Selects that load users as a relation of something else; the index page itself has no "where in".
     *
     * @return list<string>
     */
    private function relatedUserSelects(): array
    {
        return array_values(array_filter(
            $this->selectsAgainst(User::class),
            fn (string $query): bool => str_contains($query, '"users"."id" in'),
        ));
    }

    private function assertNoRelatedUserSelects(): void
    {
        $this->assertSame([], $this->relatedUserSelects());
    }

    /**
     * @return array<string, mixed>
     */
    private function firstRowWith(TestResponse $response, string $relation): array
    {
        foreach ($response->json('data') as $row) {
            if (! empty($row['relationships'][$relation])) {
                return $row;
            }
        }

        $this->fail("No row came back with [{$relation}].");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function relatedOfTheFirstRowWith(TestResponse $response, string $relation): array
    {
        return $this->firstRowWith($response, $relation)['relationships'][$relation];
    }
}

class ProbedUser extends User
{
    protected $table = 'users';

    public function posts(): HasManyRelation
    {
        return $this->hasMany(ProbedPost::class, 'user_id');
    }
}

class ProbedPost extends Post
{
    /**
     * @var list<string>
     */
    public static array $called = [];

    protected $table = 'posts';

    public function probe(): BelongsToRelation
    {
        static::$called[] = 'probe';

        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Not a method named "forwarded": calling that on the model reaches this scope through the query builder.
     *
     * @param  Builder<static>  $query
     */
    public function scopeForwarded(Builder $query): void
    {
        static::$called[] = 'forwarded';
    }
}

class ProbedUserRepository extends Repository
{
    public static string $model = ProbedUser::class;

    public static string $uriKey = 'probed-users';

    public static array $related = ['posts'];

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}

class McpUserWithPostsRepository extends Repository
{
    use HasMcpTools;

    public static string $model = User::class;

    public static string $uriKey = 'mcp-users-with-posts';

    public static array $related = ['posts'];

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}

class CommentWithUserRepository extends CommentRepository
{
    public static string $uriKey = 'comments-with-user';

    public static function include(): array
    {
        return [BelongsTo::make('user', UserRepository::class)];
    }
}
