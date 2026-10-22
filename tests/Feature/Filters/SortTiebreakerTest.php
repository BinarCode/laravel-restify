<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Binaryk\LaravelRestify\Tests\Fixtures\Company\CompanyUserPivot;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class SortTiebreakerTest extends IntegrationTestCase
{
    public function test_rows_tied_on_an_ascending_sort_are_ordered_by_ascending_primary_key(): void
    {
        UserRepository::$sort = ['name'];

        $first = User::factory()->create(['name' => 'Same']);
        $second = User::factory()->create(['name' => 'Same']);
        $third = User::factory()->create(['name' => 'Same']);
        $alpha = User::factory()->create(['name' => 'Alpha']);

        $ids = $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->assertOk()
            ->json('data.*.id');

        $this->assertSame(
            [(string) $alpha->id, (string) $first->id, (string) $second->id, (string) $third->id],
            array_map('strval', $ids),
        );
    }

    public function test_rows_tied_on_a_descending_sort_are_ordered_by_descending_primary_key(): void
    {
        UserRepository::$sort = ['name'];

        $alpha = User::factory()->create(['name' => 'Alpha']);
        $first = User::factory()->create(['name' => 'Same']);
        $second = User::factory()->create(['name' => 'Same']);

        $ids = $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->assertOk()
            ->json('data.*.id');

        $this->assertSame(
            [(string) $second->id, (string) $first->id, (string) $alpha->id],
            array_map('strval', $ids),
        );
    }

    public function test_pages_of_a_tied_sort_return_every_row_exactly_once(): void
    {
        UserRepository::$sort = ['name'];

        $users = User::factory()->count(5)->create(['name' => 'Same']);

        $ids = collect(range(1, 3))->flatMap(fn (int $page): array => $this->getJson(UserRepository::route(query: [
            'sort' => 'name',
            'perPage' => 2,
            'page' => $page,
        ]))->assertOk()->json('data.*.id'))->map(static fn (mixed $id): string => (string) $id);

        $this->assertSame(
            $users->sortBy('id')->pluck('id')->map(static fn (mixed $id): string => (string) $id)->values()->all(),
            $ids->all(),
        );
    }

    public function test_tiebreaker_follows_the_direction_of_the_last_requested_sort(): void
    {
        Post::factory()->create();

        PostRepository::$sort = ['title', 'is_active'];

        $this->assertStringEndsWith(
            'order by "posts"."is_active" desc, "posts"."title" asc, "posts"."id" asc limit 15 offset 0',
            $this->indexSql(PostRepository::route(query: ['sort' => '-is_active,title'])),
        );

        $this->assertStringEndsWith(
            'order by "posts"."title" asc, "posts"."is_active" desc, "posts"."id" desc limit 15 offset 0',
            $this->indexSql(PostRepository::route(query: ['sort' => 'title,-is_active'])),
        );
    }

    public function test_sorting_by_the_primary_key_adds_no_second_key_order(): void
    {
        Post::factory()->create();

        PostRepository::$sort = ['id', 'title'];

        $this->assertStringEndsWith(
            'order by "posts"."title" asc, "posts"."id" asc limit 15 offset 0',
            $this->indexSql(PostRepository::route(query: ['sort' => 'title,id'])),
        );
    }

    public function test_unsorted_index_keeps_a_single_newest_first_order(): void
    {
        Post::factory()->create();

        $this->assertStringEndsWith(
            'order by "posts"."id" desc limit 15 offset 0',
            $this->indexSql(PostRepository::route()),
        );
    }

    public function test_index_query_orders_rank_before_the_tiebreaker(): void
    {
        PostRepository::$sort = ['title'];

        $_SERVER['restify.post.indexQueryCallback'] = static function ($query): void {
            $query->orderBy('posts.description');
        };

        $later = Post::factory()->create(['title' => 'Same', 'description' => 'A']);
        $earlier = Post::factory()->create(['title' => 'Same', 'description' => 'B']);

        $this->assertSame(
            [(string) $later->id, (string) $earlier->id],
            array_map('strval', $this->getJson(PostRepository::route(query: ['sort' => 'title']))->assertOk()->json('data.*.id')),
        );
    }

    public function test_belongs_to_relation_sort_tiebreaks_on_the_qualified_primary_key(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'user' => SortableFilter::make()
                ->setColumn('name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class)),
        ];

        $user = User::factory()->create(['name' => 'Same']);
        $first = Post::factory()->create(['user_id' => $user->id]);
        $second = Post::factory()->create(['user_id' => $user->id]);

        $this->assertSame(
            [(string) $first->id, (string) $second->id],
            array_map('strval', $this->getJson(PostRepository::route(query: ['related' => 'user', 'sort' => 'user']))
                ->assertOk()
                ->json('data.*.id')),
        );
    }

    public function test_relation_queries_tiebreak_on_the_qualified_primary_key(): void
    {
        $service = new class extends RepositorySearchService
        {
            public function tiebreak(Relation $query): Relation
            {
                return $this->applyKeyTiebreaker($query);
            }
        };

        $user = User::factory()->create();

        $this->assertStringEndsWith(
            'order by "posts"."title" asc, "posts"."id" asc',
            $service->tiebreak($user->posts()->orderBy('posts.title'))->toSql(),
        );

        $this->assertStringEndsWith(
            'order by "posts"."title" desc, "posts"."id" desc',
            $service->tiebreak($user->posts()->orderByDesc('posts.title'))->toSql(),
        );

        $this->assertStringEndsWith(
            'order by length(title), "posts"."id" desc',
            $service->tiebreak($user->posts()->orderByRaw('length(title)'))->toSql(),
        );
    }

    public function test_pivot_queries_get_no_tiebreaker(): void
    {
        $service = new class extends RepositorySearchService
        {
            public function tiebreak(Builder $query): Builder
            {
                return $this->applyKeyTiebreaker($query);
            }
        };

        $this->assertStringEndsWith(
            'order by "created_at" asc',
            $service->tiebreak(CompanyUserPivot::query()->orderBy('created_at'))->toSql(),
        );
    }

    public function test_grouped_queries_get_no_tiebreaker(): void
    {
        UserRepository::$sort = ['name'];
        UserRepository::$groupBy = ['name'];

        User::factory()->count(2)->create(['name' => 'Same']);

        $sql = $this->indexSql(UserRepository::route(query: ['sort' => 'name', 'group_by' => 'name']));

        $this->assertStringContainsString('group by "users"."name"', $sql);
        $this->assertStringEndsWith('order by "users"."name" asc limit 15 offset 0', $sql);
    }

    public function test_distinct_queries_get_no_tiebreaker(): void
    {
        PostRepository::$sort = ['title'];

        $_SERVER['restify.post.indexQueryCallback'] = static function ($query): void {
            $query->distinct();
        };

        Post::factory()->create();

        $this->assertStringEndsWith(
            'order by "posts"."title" asc limit 15 offset 0',
            $this->indexSql(PostRepository::route(query: ['sort' => 'title'])),
        );
    }

    private function indexSql(string $uri): string
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson($uri)->assertOk();

        $query = collect(DB::getQueryLog())->first(
            static fn (array $query): bool => str_contains($query['query'], ' limit ')
        );

        $this->assertNotNull($query, 'Expected a paginated index query');

        return $query['query'];
    }
}
