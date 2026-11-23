<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RepositoryApplyFiltersRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\MCP\Requests\McpIndexRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\Comment;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\IndexCachePostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\ValueFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Closure;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

/**
 * With the index cache enabled, two index requests share a cached page only when they would get the same response.
 *
 * Three posts are listed, newest first: Gamma (sport, active), Beta (news, inactive) and Alpha (news, active).
 * Alpha and Gamma belong to the author, Beta to the editor.
 */
class IndexCacheKeyCoversRequestTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private User $author;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->enableRepositoryCache();

        Restify::repositories([IndexCachePostRepository::class]);

        $this->author = User::factory()->create();
        $this->editor = User::factory()->create();

        $alpha = Post::factory()->create(['user_id' => $this->author->id, 'title' => 'Alpha', 'category' => 'news', 'is_active' => true]);
        Post::factory()->create(['user_id' => $this->editor->id, 'title' => 'Beta', 'category' => 'news', 'is_active' => false]);
        $gamma = Post::factory()->create(['user_id' => $this->author->id, 'title' => 'Gamma', 'category' => 'sport', 'is_active' => true]);

        Comment::factory(3)->create(['post_id' => $alpha->id, 'user_id' => $this->author->id]);
        Comment::factory(3)->create(['post_id' => $gamma->id, 'user_id' => $this->author->id]);

        $this->recordQueries();
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  list<string>  $firstTitles
     * @param  array<string, mixed>  $second
     * @param  list<string>  $secondTitles
     */
    #[Test]
    #[DataProvider('requests_that_differ')]
    public function requests_that_differ_get_their_own_cached_page(
        array $first,
        array $firstTitles,
        array $second,
        array $secondTitles,
    ): void {
        $this->onAMissThenOnAHit(function (string $round) use ($first, $firstTitles, $second, $secondTitles): void {
            $this->assertSame($firstTitles, $this->titles($this->index($first)), $round);
            $this->assertSame($secondTitles, $this->titles($this->index($second)), $round);
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    /**
     * @return array<string, array{array<string, mixed>, list<string>, array<string, mixed>, list<string>}>
     */
    public static function requests_that_differ(): array
    {
        $filters = static fn (string $title): string => base64_encode((string) json_encode([[
            'key' => ValueFilter::uriKey(),
            'value' => $title,
            'operator' => '=',
            'column' => 'title',
        ]]));

        return [
            'two match filters' => [
                ['title' => 'Alpha'], ['Alpha'],
                ['title' => 'Beta'], ['Beta'],
            ],
            'no match filter, then one' => [
                [], ['Gamma', 'Beta', 'Alpha'],
                ['title' => 'Alpha'], ['Alpha'],
            ],
            'a match filter, then none' => [
                ['title' => 'Alpha'], ['Alpha'],
                [], ['Gamma', 'Beta', 'Alpha'],
            ],
            'a match filter and its negation' => [
                ['title' => 'Alpha'], ['Alpha'],
                ['-title' => 'Alpha'], ['Gamma', 'Beta'],
            ],
            'two match filters under filter[]' => [
                ['filter' => ['title' => 'Alpha']], ['Alpha'],
                ['filter' => ['title' => 'Beta']], ['Beta'],
            ],
            'a match filter under filter[] and its negation' => [
                ['filter' => ['title' => 'Alpha']], ['Alpha'],
                ['filter' => ['-title' => 'Alpha']], ['Gamma', 'Beta'],
            ],
            'two values of a boolean match filter' => [
                ['is_active' => 'true'], ['Gamma', 'Alpha'],
                ['is_active' => 'false'], ['Beta'],
            ],
            'two sorts' => [
                ['sort' => 'title'], ['Alpha', 'Beta', 'Gamma'],
                ['sort' => '-title'], ['Gamma', 'Beta', 'Alpha'],
            ],
            'two search terms' => [
                ['search' => 'Alp'], ['Alpha'],
                ['search' => 'Gam'], ['Gamma'],
            ],
            'two page sizes as perPage' => [
                ['perPage' => 1], ['Gamma'],
                ['perPage' => 2], ['Gamma', 'Beta'],
            ],
            'two page sizes as page[size]' => [
                ['page' => ['size' => 1]], ['Gamma'],
                ['page' => ['size' => 2]], ['Gamma', 'Beta'],
            ],
            'two pages' => [
                ['perPage' => 1, 'page' => 1], ['Gamma'],
                ['perPage' => 1, 'page' => 2], ['Beta'],
            ],
            'two pages as page[number]' => [
                ['page' => ['size' => 1, 'number' => 2]], ['Beta'],
                ['page' => ['size' => 1, 'number' => 3]], ['Alpha'],
            ],
            'two advanced filters' => [
                ['filters' => $filters('Alpha')], ['Alpha'],
                ['filters' => $filters('Beta')], ['Beta'],
            ],
            'two values of a query parameter read in indexQuery()' => [
                ['in_category' => 'news'], ['Beta', 'Alpha'],
                ['in_category' => 'sport'], ['Gamma'],
            ],
            'a query parameter read in indexQuery(), then none' => [
                ['in_category' => 'sport'], ['Gamma'],
                [], ['Gamma', 'Beta', 'Alpha'],
            ],
            'the same match filter with another query parameter' => [
                ['is_active' => 'true', 'in_category' => 'news'], ['Alpha'],
                ['is_active' => 'true', 'in_category' => 'sport'], ['Gamma'],
            ],
        ];
    }

    #[Test]
    public function related_and_include_and_each_list_of_relationships_get_their_own_cached_page(): void
    {
        $relationships = fn (array $query): array => array_keys($this->index($query)->json('data.0.relationships') ?? []);

        $this->onAMissThenOnAHit(function (string $round) use ($relationships): void {
            $this->assertSame([], $relationships([]), $round);
            $this->assertSame(['user'], $relationships(['include' => 'user']), $round);
            $this->assertSame(['user'], $relationships(['related' => 'user']), $round);
            $this->assertSame(['comments'], $relationships(['include' => 'comments']), $round);
            $this->assertSame(['comments'], $relationships(['related' => 'comments']), $round);
            $this->assertSame(['user', 'comments'], $relationships(['include' => 'user,comments']), $round);
        });

        $this->assertServedFromTheCache(requests: 6);
        $this->assertSame(6, $this->cachedPages(), 'related and include are compared as sent, not as aliases');
    }

    #[Test]
    public function each_relatable_per_page_gets_its_own_cached_page(): void
    {
        $comments = fn (array $query): int => count($this->index($query)->json('data.0.relationships.comments'));

        $this->onAMissThenOnAHit(function (string $round) use ($comments): void {
            $this->assertSame(3, $comments(['include' => 'comments']), $round);
            $this->assertSame(1, $comments(['include' => 'comments', 'relatablePerPage' => 1]), $round);
            $this->assertSame(2, $comments(['include' => 'comments', 'relatablePerPage' => 2]), $round);
            $this->assertSame(1, $comments(['include' => 'comments', 'relatablePerPage' => ['comments' => 1]]), $round);
        });

        $this->assertServedFromTheCache(requests: 4);
    }

    #[Test]
    public function a_request_with_meta_and_one_without_get_their_own_cached_page(): void
    {
        config(['restify.repositories.serialize_index_meta' => false]);

        $this->onAMissThenOnAHit(function (): void {
            $this->index()->assertJsonMissingPath('data.0.meta');
            $this->index(['withMeta' => 'true'])->assertJsonPath('data.0.meta.authorizedToShow', true);
            $this->index(['withMeta' => 'false'])->assertJsonMissingPath('data.0.meta');
        });

        $this->assertServedFromTheCache(requests: 3);
    }

    #[Test]
    public function a_grouped_request_and_an_ungrouped_one_get_their_own_cached_page(): void
    {
        $this->onAMissThenOnAHit(function (): void {
            $this->index(['group_by' => 'category'])->assertJsonCount(2, 'data');
            $this->index()->assertJsonCount(3, 'data');
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function each_set_of_advanced_filters_posted_in_the_body_gets_its_own_cached_page(): void
    {
        $this->onAMissThenOnAHit(function (string $round): void {
            $this->assertSame(['Alpha'], $this->titles($this->applyFilters([$this->titleFilter('Alpha')])), $round);
            $this->assertSame(['Beta'], $this->titles($this->applyFilters([$this->titleFilter('Beta')])), $round);
            $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles($this->applyFilters([])), $round);
        });

        $this->assertServedFromTheCache(requests: 3);
    }

    #[Test]
    public function the_posted_advanced_filters_are_keyed_by_their_query_string_and_their_body(): void
    {
        $route = fn (array $query): string => IndexCachePostRepository::route('apply-restify-advanced-filters', query: $query);

        $this->onAMissThenOnAHit(function (string $round) use ($route): void {
            $this->assertSame(['Alpha'], $this->titles($this->postJson($route(['title' => 'Alpha']))), $round);
            $this->assertSame(['Beta'], $this->titles($this->postJson($route(['title' => 'Beta']))), $round);
            $this->assertSame(['Gamma'], $this->titles($this->postJson($route([]), ['in_category' => 'sport'])), $round);
            $this->assertSame(['Beta', 'Alpha'], $this->titles($this->postJson($route([]), ['in_category' => 'news'])), $round);
        });

        $this->assertServedFromTheCache(requests: 4);
    }

    /**
     * A match filter applies only when the query string names it, and then reads its value from the body first.
     * The same body therefore lists different rows with and without the filter in the query string.
     */
    #[Test]
    public function the_same_body_with_and_without_the_filter_in_the_query_string_get_their_own_cached_page(): void
    {
        $route = fn (array $query): string => IndexCachePostRepository::route('apply-restify-advanced-filters', query: $query);

        $this->onAMissThenOnAHit(function (string $round) use ($route): void {
            $this->assertSame(
                ['Beta'],
                $this->titles($this->postJson($route(['title' => 'Alpha']), ['title' => 'Beta'])),
                $round,
            );

            $this->assertSame(
                ['Gamma', 'Beta', 'Alpha'],
                $this->titles($this->postJson($route([]), ['title' => 'Beta'])),
                $round,
            );
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function the_index_and_the_posted_advanced_filters_get_their_own_cached_page(): void
    {
        $this->onAMissThenOnAHit(function (string $round): void {
            $index = $this->index();
            $filtered = $this->applyFilters([]);

            $this->assertStringEndsWith('/index-cache-posts', $index->json('meta.path'), $round);
            $this->assertStringEndsWith('/apply-restify-advanced-filters', $filtered->json('meta.path'), $round);
            $this->assertStringEndsWith('/apply-restify-advanced-filters?page=1', $filtered->json('links.first'), $round);
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    /**
     * The pagination links of the response carry the URL the request came in on.
     */
    #[Test]
    public function each_host_gets_its_own_cached_page(): void
    {
        $route = IndexCachePostRepository::route();

        $this->onAMissThenOnAHit(function () use ($route): void {
            $this->getJson('http://one.restify.test'.$route)
                ->assertOk()
                ->assertJsonPath('links.first', 'http://one.restify.test'.$route.'?page=1');

            $this->getJson('http://two.restify.test'.$route)
                ->assertOk()
                ->assertJsonPath('links.first', 'http://two.restify.test'.$route.'?page=1');
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function each_parent_and_each_request_of_a_nested_index_get_their_own_cached_page(): void
    {
        UserRepository::$related = ['posts' => HasMany::make('posts', PostRepository::class)];

        $nested = fn (User $parent, array $query = []): array => $this->titles(
            $this->getJson(UserRepository::route("{$parent->id}/posts", query: $query))
        );

        $this->onAMissThenOnAHit(function (string $round) use ($nested): void {
            $this->assertSame(['Gamma', 'Alpha'], $nested($this->author), $round);
            $this->assertSame(['Beta'], $nested($this->editor), $round);
            $this->assertSame(['Alpha'], $nested($this->author, ['title' => 'Alpha']), $round);
            $this->assertSame(['Gamma'], $nested($this->author, ['title' => 'Gamma']), $round);
            $this->assertSame([], $nested($this->editor, ['title' => 'Alpha']), $round);
            $this->assertSame(['Alpha'], $this->titles($this->getJson(PostRepository::route(query: ['title' => 'Alpha']))), $round);
        });

        $this->assertServedFromTheCache(requests: 6);
    }

    #[Test]
    public function an_identical_request_is_served_from_the_cache(): void
    {
        $query = ['-title' => 'Gamma', 'in_category' => 'news', 'include' => 'user', 'sort' => 'title', 'perPage' => 2];

        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($query)));

        $onMiss = count($this->selectsAgainst(Post::class));

        $this->recordQueries();

        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($query)));

        $this->assertGreaterThan(1, $onMiss);
        $this->assertServedFromTheCache(requests: 1);
        $this->assertSame(1, $this->cachedPages());
    }

    /**
     * @param  array<string, mixed>  $first
     * @param  array<string, mixed>  $second
     */
    #[Test]
    #[DataProvider('equivalent_requests')]
    public function requests_that_only_differ_in_their_notation_share_a_cached_page(array $first, array $second): void
    {
        $firstResponse = $this->index($first)->json();

        $this->recordQueries();

        $this->assertSame($firstResponse, $this->index($second)->json());

        $this->assertServedFromTheCache(requests: 1);
        $this->assertSame(1, $this->cachedPages());
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function equivalent_requests(): array
    {
        return [
            'the order of the parameters' => [
                ['is_active' => 'true', 'in_category' => 'news', 'sort' => 'title', 'include' => 'user'],
                ['include' => 'user', 'sort' => 'title', 'in_category' => 'news', 'is_active' => 'true'],
            ],
            'the order of the keys of a nested parameter' => [
                ['filter' => ['-title' => 'Beta', 'is_active' => 'true'], 'include' => 'comments', 'relatablePerPage' => ['comments' => 1, 'user' => 1]],
                ['relatablePerPage' => ['user' => 1, 'comments' => 1], 'include' => 'comments', 'filter' => ['is_active' => 'true', '-title' => 'Beta']],
            ],
            'the order of page[size] and page[number]' => [
                ['page' => ['size' => 2, 'number' => 1]],
                ['page' => ['number' => 1, 'size' => 2]],
            ],
            'no page, and the first page' => [
                [],
                ['page' => 1],
            ],
            'no page size, and the default page size' => [
                [],
                ['perPage' => 15],
            ],
            'perPage and page[size]' => [
                ['perPage' => 2, 'page' => 2],
                ['page' => ['size' => 2, 'number' => 2]],
            ],
            'a page size above the maximum, and the maximum' => [
                ['perPage' => 100],
                ['perPage' => 5000],
            ],
        ];
    }

    /**
     * Each kind of request serializes its own fields and reads the input its own way, also on the same URL.
     */
    #[Test]
    public function each_kind_of_request_gets_its_own_key(): void
    {
        $repository = IndexCachePostRepository::resolveWith(new Post);

        $keys = array_map(
            fn (string $request): string => $repository->generateIndexCacheKey(
                $request::create(IndexCachePostRepository::route(), 'GET', ['title' => 'Alpha'])
            ),
            [RestifyRequest::class, RepositoryIndexRequest::class, RepositoryApplyFiltersRequest::class, McpIndexRequest::class],
        );

        $this->assertCount(4, array_unique($keys));
    }

    #[Test]
    public function the_items_of_a_list_parameter_are_compared_in_order(): void
    {
        $this->assertNotSame(
            $this->cacheKey(['ids' => ['1', '2']]),
            $this->cacheKey(['ids' => ['2', '1']]),
        );

        $this->assertSame(
            $this->cacheKey(['ids' => ['1', '2'], 'sort' => 'title']),
            $this->cacheKey(['sort' => 'title', 'ids' => ['1', '2']]),
        );
    }

    /**
     * A request that names no page leaves the page to the paginator, which reads it from the current request.
     */
    #[Test]
    public function a_request_without_a_page_is_keyed_by_the_page_the_paginator_falls_back_to(): void
    {
        $repository = IndexCachePostRepository::resolveWith(new Post);

        $withoutPage = fn (): RepositoryIndexRequest => RepositoryIndexRequest::create(
            IndexCachePostRepository::route(),
            'GET',
            ['perPage' => 1],
        );

        $titles = fn (): array => array_column(
            array_column($repository->index($withoutPage())->getData(true)['data'], 'attributes'),
            'title',
        );

        $this->onAMissThenOnAHit(function (string $round) use ($titles): void {
            $this->index(['page' => 2]);
            $this->assertSame(['Beta'], $titles(), $round);

            $this->index(['page' => 3]);
            $this->assertSame(['Alpha'], $titles(), $round);
        });
    }

    #[Test]
    public function storing_updating_and_deleting_through_the_repository_invalidate_every_cached_page(): void
    {
        $news = ['in_category' => 'news', 'sort' => 'title'];
        $sport = ['in_category' => 'sport', 'sort' => 'title'];

        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($news)));
        $this->assertSame(['Gamma'], $this->titles($this->index($sport)));

        $id = $this->postJson(IndexCachePostRepository::route(), ['title' => 'Delta', 'category' => 'news'])
            ->assertCreated()
            ->json('data.id');

        $this->assertSame(['Alpha', 'Beta', 'Delta'], $this->titles($this->index($news)));
        $this->assertSame(['Gamma'], $this->titles($this->index($sport)));

        $this->putJson(IndexCachePostRepository::route($id), ['title' => 'Epsilon', 'category' => 'sport'])->assertOk();

        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($news)));
        $this->assertSame(['Epsilon', 'Gamma'], $this->titles($this->index($sport)));

        $this->deleteJson(IndexCachePostRepository::route($id))->assertNoContent();

        $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($news)));
        $this->assertSame(['Gamma'], $this->titles($this->index($sport)));
    }

    #[Test]
    public function clear_cache_invalidates_every_cached_page_of_the_repository(): void
    {
        $queries = [[], ['title' => 'Alpha'], ['in_category' => 'news'], ['include' => 'user', 'title' => 'Alpha']];

        foreach ($queries as $query) {
            $this->index($query)->assertJsonMissing(['description' => 'changed-without-events']);
        }

        $this->assertSame(count($queries), $this->cachedPages());

        DB::table((new Post)->getTable())->update(['description' => 'changed-without-events']);

        foreach ($queries as $query) {
            $this->index($query)->assertJsonMissing(['description' => 'changed-without-events']);
        }

        IndexCachePostRepository::clearCache();

        foreach ($queries as $query) {
            $this->index($query)->assertJsonFragment(['description' => 'changed-without-events']);
        }
    }

    #[Test]
    public function two_users_sending_the_same_request_get_their_own_cached_page(): void
    {
        $_SERVER['restify.post.show.callback'] = fn (User $user, Post $post): bool => $post->user_id === $user->id;

        $query = ['in_category' => 'news', 'sort' => 'title'];

        $this->onAMissThenOnAHit(function (string $round) use ($query): void {
            $this->authenticate($this->author);
            $this->assertSame(['Alpha'], $this->titles($this->index($query)), $round);

            $this->authenticate($this->editor);
            $this->assertSame(['Beta'], $this->titles($this->index($query)), $round);
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function guests_share_a_cached_page_only_for_the_same_request(): void
    {
        $this->logout();

        $this->onAMissThenOnAHit(function (string $round): void {
            $this->assertSame(['Beta', 'Alpha'], $this->titles($this->index(['in_category' => 'news'])), $round);
            $this->assertSame(['Gamma'], $this->titles($this->index(['in_category' => 'sport'])), $round);
            $this->assertSame(['Alpha'], $this->titles($this->index(['title' => 'Alpha'])), $round);
        });

        $this->assertServedFromTheCache(requests: 3);
        $this->assertStringContainsString(':guest:', $this->cacheKey(['in_category' => 'news']));
    }

    #[Test]
    public function one_user_on_two_guards_gets_a_cached_page_per_guard(): void
    {
        config(['auth.guards.admin' => ['driver' => 'session', 'provider' => 'users']]);

        $_SERVER['restify.post.show.callback'] = fn (User $user, Post $post): bool => Auth::getDefaultDriver() === 'admin'
            || $post->user_id === $user->id;

        $query = ['in_category' => 'news', 'sort' => 'title'];

        $this->onAMissThenOnAHit(function (string $round) use ($query): void {
            $this->actingAs($this->author, 'web');
            $this->assertSame(['Alpha'], $this->titles($this->index($query)), $round);

            $this->actingAs($this->author, 'admin');
            $this->assertSame(['Alpha', 'Beta'], $this->titles($this->index($query)), $round);
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function the_key_keeps_its_segments(): void
    {
        $this->authenticate($this->author);

        $model = preg_quote($this->author->getMorphClass(), '/');

        $this->assertMatchesRegularExpression(
            "/^restify:repository:index-cache-posts:index:[0-9a-f]{32}:guard_web:model_{$model}:user_{$this->author->id}:v_\d+:ver_\w+$/",
            $this->cacheKey(['title' => 'Alpha', 'in_category' => 'news']),
        );

        UserRepository::$related = ['posts' => HasMany::make('posts', PostRepository::class)];

        $this->getJson(UserRepository::route("{$this->editor->id}/posts"))->assertOk();

        $this->assertMatchesRegularExpression(
            "/^restify:repository:posts:index:parent_users_{$this->editor->id}:[0-9a-f]{32}:guard_web:model_{$model}:user_{$this->author->id}:v_\d+:ver_\w+$/",
            PostRepository::resolveWith(new Post)->generateIndexCacheKey(app(RepositoryIndexRequest::class)),
        );
    }

    /**
     * What the response depends on outside the request, such as the locale, is added by the repository.
     */
    #[Test]
    public function a_repository_can_extend_the_key_with_what_the_request_does_not_carry(): void
    {
        Restify::repositories([LocalizedIndexCachePostRepository::class]);

        $locale = fn (): string => $this->getJson(LocalizedIndexCachePostRepository::route())
            ->assertOk()
            ->json('data.0.attributes.locale');

        $this->onAMissThenOnAHit(function (string $round) use ($locale): void {
            app()->setLocale('en');
            $this->assertSame('en', $locale(), $round);

            app()->setLocale('fr');
            $this->assertSame('fr', $locale(), $round);
        });

        $this->assertServedFromTheCache(requests: 2);
    }

    #[Test]
    public function input_that_cannot_be_serialized_is_never_served_to_another_request(): void
    {
        $request = fn (): RepositoryIndexRequest => tap(
            RepositoryIndexRequest::create(IndexCachePostRepository::route(), 'GET'),
            fn (RepositoryIndexRequest $request) => $request->query->set('options', ['callback' => fn (): int => 1]),
        );

        $repository = IndexCachePostRepository::resolveWith(new Post);

        $key = $repository->generateIndexCacheKey($request());

        $this->assertNotSame($key, $repository->generateIndexCacheKey($request()));
        $this->assertMatchesRegularExpression('/^restify:repository:index-cache-posts:index:[0-9a-f]{32}:/', $key);
        $this->assertCount(3, $repository->index($request())->getData(true)['data']);
    }

    /**
     * Run the requests twice: the first run fills the cache, the second is served from it.
     *
     * @param  Closure(string): void  $requests
     */
    private function onAMissThenOnAHit(Closure $requests): void
    {
        $requests('on a cache miss');

        $this->recordQueries();

        $requests('on a cache hit');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function index(array $query = []): TestResponse
    {
        return $this->getJson(IndexCachePostRepository::route(query: $query))->assertOk();
    }

    /**
     * @param  list<array<string, mixed>>  $filters
     */
    private function applyFilters(array $filters): TestResponse
    {
        return $this->postJson(IndexCachePostRepository::route('apply-restify-advanced-filters'), [
            'filters' => $filters,
        ])->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function titleFilter(string $title): array
    {
        return [
            'key' => ValueFilter::uriKey(),
            'value' => $title,
            'operator' => '=',
            'column' => 'title',
        ];
    }

    /**
     * @return list<string>
     */
    private function titles(TestResponse $response): array
    {
        $response->assertOk();

        return $response->json('data.*.attributes.title');
    }

    /**
     * The key of the index request the route would build for the query.
     *
     * @param  array<string, mixed>  $query
     */
    private function cacheKey(array $query = []): string
    {
        $request = RepositoryIndexRequest::create(url(IndexCachePostRepository::route()), 'GET', $query);
        $request->setUserResolver(fn (?string $guard = null) => Auth::guard($guard)->user());

        return IndexCachePostRepository::resolveWith(new Post)->generateIndexCacheKey($request);
    }

    /**
     * A cache hit only reads the newest timestamp for the key, never the posts themselves.
     */
    private function assertServedFromTheCache(int $requests): void
    {
        $selects = $this->selectsAgainst(Post::class);

        $this->assertCount($requests, $selects, 'unexpected selects: '.json_encode($selects, JSON_PRETTY_PRINT));

        foreach ($selects as $select) {
            $this->assertStringContainsString('max("updated_at")', $select);
        }
    }

    /**
     * The number of index pages the array store holds for the repository.
     */
    private function cachedPages(): int
    {
        $store = Cache::getStore();

        $this->assertInstanceOf(ArrayStore::class, $store);

        $entries = (new ReflectionProperty($store, 'storage'))->getValue($store);

        return count(array_filter(
            array_keys($entries),
            fn (string $key): bool => str_contains($key, 'restify:repository:index-cache-posts:index:'),
        ));
    }
}

/**
 * A posts repository whose response depends on the locale, which no request parameter carries.
 */
class LocalizedIndexCachePostRepository extends IndexCachePostRepository
{
    public static string $uriKey = 'localized-index-cache-posts';

    public function generateIndexCacheKey(RestifyRequest $request): string
    {
        return parent::generateIndexCacheKey($request).':locale_'.app()->getLocale();
    }

    public function fields(RestifyRequest $request): array
    {
        return [
            field('locale', fn (): string => app()->getLocale()),
        ];
    }
}
