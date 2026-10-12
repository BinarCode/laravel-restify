<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\Company;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RelatablePerPageOnIndexTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private const POSTS_PER_USER = 6;

    private const COMMENTS_PER_POST = 4;

    protected function setUp(): void
    {
        parent::setUp();

        UserRepository::$related = ['posts', 'posts.comments', 'comments'];

        foreach (User::factory(3)->create() as $user) {
            foreach (Post::factory(self::POSTS_PER_USER)->create(['user_id' => $user->id]) as $post) {
                Comment::factory(self::COMMENTS_PER_POST)->create(['post_id' => $post->id, 'user_id' => $user->id]);
            }
        }

        $this->recordQueries();
    }

    #[Test]
    public function index_related_loads_are_unlimited_without_opting_in(): void
    {
        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts']))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [6, 6, 6]);
    }

    /**
     * @param  list<int>  $expected
     */
    #[Test]
    #[TestWith([2, [2, 2, 2]], 'below the rows available')]
    #[TestWith([100, [6, 6, 6]], 'above the rows available')]
    public function a_global_relatable_per_page_limits_every_parent_with_one_query(int $relatablePerPage, array $expected): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts',
            'relatablePerPage' => $relatablePerPage,
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', $expected);
        $this->assertSelectCount(1, Post::class);
    }

    #[Test]
    public function the_max_per_page_config_opts_in_and_limits_to_the_default_clamped(): void
    {
        config(['restify.pagination.max_per_page' => 3]);

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts']))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [3, 3, 3]);
    }

    #[Test]
    public function the_max_per_page_config_above_the_default_does_not_raise_it(): void
    {
        config(['restify.pagination.max_per_page' => 100]);

        $response = $this->getJson(UserRepository::route(query: ['related' => 'posts']))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [6, 6, 6]);
    }

    #[Test]
    public function the_max_per_page_config_clamps_a_larger_relatable_per_page(): void
    {
        config(['restify.pagination.max_per_page' => 4]);

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts',
            'relatablePerPage' => 5,
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [4, 4, 4]);
    }

    #[Test]
    public function a_per_relation_value_limits_only_its_relation(): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts,comments',
            'relatablePerPage' => ['posts' => 2],
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [2, 2, 2]);
        $this->assertRelatedCounts($response, 'comments', [15, 15, 15]);
    }

    #[Test]
    public function per_relation_values_are_independent(): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts,comments',
            'relatablePerPage' => ['posts' => 2, 'comments' => 5],
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [2, 2, 2]);
        $this->assertRelatedCounts($response, 'comments', [5, 5, 5]);
    }

    #[Test]
    public function a_per_relation_value_is_clamped_by_the_max_per_page_config(): void
    {
        config(['restify.pagination.max_per_page' => 4]);

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts',
            'relatablePerPage' => ['posts' => 5],
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [4, 4, 4]);
    }

    #[Test]
    #[TestWith(['abc'], 'non-numeric')]
    #[TestWith([-1], 'negative')]
    public function an_invalid_per_relation_value_falls_back_to_the_default(int|string $value): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts',
            'relatablePerPage' => ['posts' => $value],
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [6, 6, 6]);
    }

    #[Test]
    public function a_nested_path_is_limited_per_parent_at_its_own_level(): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts.comments',
            'relatablePerPage' => ['posts.comments' => 3],
        ]))->assertOk();

        $posts = $response->json('data.0.relationships')['posts.comments'];

        $this->assertCount(self::POSTS_PER_USER, $posts);

        foreach ($posts as $post) {
            $this->assertCount(3, $post['comments']);
        }

        $this->assertSelectCount(1, Comment::class);
    }

    #[Test]
    public function a_global_value_limits_every_level_of_a_nested_path(): void
    {
        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts.comments',
            'relatablePerPage' => 2,
        ]))->assertOk();

        $posts = $response->json('data.0.relationships')['posts.comments'];

        $this->assertCount(2, $posts);

        foreach ($posts as $post) {
            $this->assertCount(2, $post['comments']);
        }
    }

    #[Test]
    public function belongs_to_relations_are_not_limited(): void
    {
        UserRepository::$related = ['posts', 'posts.user'];

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts.user',
            'relatablePerPage' => 1,
        ]))->assertOk();

        $posts = $response->json('data.0.relationships')['posts.user'];

        $this->assertCount(1, $posts);
        $this->assertNotEmpty($posts[0]['user']);
    }

    #[Test]
    public function a_belongs_to_many_relation_is_limited_per_parent_on_index(): void
    {
        foreach (Company::factory(3)->create() as $company) {
            $company->users()->attach(User::factory(5)->create()->pluck('id'));
        }

        $response = $this->getJson(CompanyRepository::route(query: [
            'related' => 'users',
            'relatablePerPage' => 2,
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'users', [2, 2, 2]);
        $this->assertStringContainsString('row_number', implode(' ', $this->selectsAgainst(User::class)));
    }

    #[Test]
    public function an_already_loaded_relation_is_limited_on_show_when_opted_in(): void
    {
        Restify::repositories([UserWithLoadedPostsRepository::class]);

        $user = User::query()->first();

        $this->getJson(UserWithLoadedPostsRepository::route($user, query: ['related' => 'posts']))
            ->assertOk()
            ->assertJsonCount(6, 'data.relationships.posts');

        $this->getJson(UserWithLoadedPostsRepository::route($user, query: [
            'related' => 'posts',
            'relatablePerPage' => 2,
        ]))->assertOk()->assertJsonCount(2, 'data.relationships.posts');

        $this->getJson(UserWithLoadedPostsRepository::route($user, query: [
            'related' => 'posts',
            'relatablePerPage' => ['posts' => 4],
        ]))->assertOk()->assertJsonCount(4, 'data.relationships.posts');
    }

    #[Test]
    public function an_already_loaded_relation_is_clamped_on_show_by_the_max_per_page_config(): void
    {
        Restify::repositories([UserWithLoadedPostsRepository::class]);

        config(['restify.pagination.max_per_page' => 3]);

        $this->getJson(UserWithLoadedPostsRepository::route(User::query()->first(), query: ['related' => 'posts']))
            ->assertOk()
            ->assertJsonCount(3, 'data.relationships.posts');
    }

    #[Test]
    public function a_per_relation_value_works_on_a_relation_that_is_not_loaded_on_show(): void
    {
        Restify::repositories([UserWithPostsFieldRepository::class]);

        $this->getJson(UserWithPostsFieldRepository::route(User::query()->first(), query: [
            'related' => 'posts',
            'relatablePerPage' => ['posts' => 2],
        ]))->assertOk()->assertJsonCount(2, 'data.relationships.posts');
    }

    #[Test]
    public function a_relation_also_named_in_the_repository_with_is_still_limited_in_the_query(): void
    {
        UserRepository::$with = ['posts'];

        $response = $this->getJson(UserRepository::route(query: [
            'related' => 'posts',
            'relatablePerPage' => 2,
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [2, 2, 2]);
        $this->assertStringContainsString('row_number', implode(' ', $this->selectsAgainst(Post::class)));
    }

    #[Test]
    public function a_morph_many_relation_is_limited_per_parent_on_index(): void
    {
        Restify::repositories([UserWithMorphTokensRepository::class]);

        foreach (UserWithMorphTokens::query()->get() as $user) {
            foreach (range(1, 5) as $index) {
                $user->tokens()->forceCreate([
                    'tokenable_type' => $user->getMorphClass(),
                    'tokenable_id' => $user->getKey(),
                    'name' => 'token',
                    'token' => hash('sha256', $user->getKey().'-'.$index),
                ]);
            }
        }

        $response = $this->getJson(UserWithMorphTokensRepository::route(query: [
            'related' => 'tokens',
            'relatablePerPage' => 2,
        ]))->assertOk();

        $this->assertRelatedCounts($response, 'tokens', [2, 2, 2]);
        $this->assertStringContainsString('row_number', implode(' ', $this->selectsAgainst(PersonalAccessToken::class)));
    }

    #[Test]
    public function a_relation_declared_with_an_eager_field_uses_the_related_repository_default(): void
    {
        Restify::repositories([UserWithPostsFieldRepository::class]);

        PostRepository::$defaultRelatablePerPage = 3;

        config(['restify.pagination.max_per_page' => 100]);

        $response = $this->getJson(UserWithPostsFieldRepository::route(query: ['related' => 'posts']))->assertOk();

        $this->assertRelatedCounts($response, 'posts', [3, 3, 3]);
    }

    /**
     * @param  list<int>  $expected
     */
    private function assertRelatedCounts(TestResponse $response, string $relation, array $expected): void
    {
        $counts = array_map(
            fn (array $row): int => count($row['relationships'][$relation] ?? []),
            $response->json('data'),
        );

        $this->assertSame($expected, $counts);
    }
}

class UserWithLoadedPostsRepository extends Repository
{
    public static string $model = User::class;

    public static string $uriKey = 'users-with-loaded-posts';

    public static array $with = ['posts'];

    public static function include(): array
    {
        return [
            HasMany::make('posts', PostRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}

class UserWithPostsFieldRepository extends Repository
{
    public static string $model = User::class;

    public static string $uriKey = 'users-with-posts-field';

    public static function include(): array
    {
        return [
            HasMany::make('posts', PostRepository::class),
        ];
    }

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}

class UserWithMorphTokens extends User
{
    protected $table = 'users';

    /**
     * @return MorphMany<PersonalAccessToken, $this>
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }
}

class UserWithMorphTokensRepository extends Repository
{
    public static string $model = UserWithMorphTokens::class;

    public static string $uriKey = 'users-with-morph-tokens';

    public static array $related = ['tokens'];

    public function fields(RestifyRequest $request): array
    {
        return [field('name')];
    }
}
