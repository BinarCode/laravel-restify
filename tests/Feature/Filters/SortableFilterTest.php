<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Filters\Sorts\NaturalSortFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SortableFilterTest extends IntegrationTestCase
{
    public function test_can_order_using_filter_sortable_definition(): void
    {
        User::factory()->create([
            'name' => 'Zoro',
        ]);

        User::factory()->create([
            'name' => 'Alisa',
        ]);

        UserRepository::$sort = [
            'name' => SortableFilter::make()->setColumn('name'),
        ];

        $this->assertSame('Alisa', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('Zoro', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.1.attributes.name'));
        $this->assertSame('Zoro', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.0.attributes.name'));
        $this->assertSame('Alisa', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.1.attributes.name'));
    }

    public function test_can_order_using_natural_sortable_filter(): void
    {
        User::factory()->create([
            'name' => '1',
        ]);

        User::factory()->create([
            'name' => '10',
        ]);

        User::factory()->create([
            'name' => '2',
        ]);

        User::factory()->create([
            'name' => '20',
        ]);

        UserRepository::$sort = [
            'name' => NaturalSortFilter::class,
        ];

        $this->assertSame('1', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('2', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.1.attributes.name'));

        $this->assertSame('10', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.2.attributes.name'));

        $this->assertSame('20', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.3.attributes.name'));

        $this->assertSame('20', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.0.attributes.name'));

        $this->assertSame('10', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.1.attributes.name'));

        $this->assertSame('2', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.2.attributes.name'));

        $this->assertSame('1', $this->getJson(UserRepository::route(query: ['sort' => '-name']))
            ->json('data.3.attributes.name'));
    }

    public function test_can_order_using_invokable(): void
    {
        $invokable = new class
        {
            public function __invoke(RestifyRequest $request, Builder $query, string $order, string $column): void
            {
                $query->orderBy($column, $order);
            }
        };

        UserRepository::$sort = [
            'name' => $invokable,
        ];

        User::factory()->create([
            'name' => 'Ana',
        ]);

        User::factory()->create([
            'name' => 'Boris',
        ]);

        $this->assertSame('Ana', $this->getJson(UserRepository::route(query: ['sort' => 'name']))
            ->json('data.0.attributes.name'));
    }

    public function test_can_sort_by_belongs_to_relation_column_absent_on_main_table(): void
    {
        // `posts` has no `name` column; `users` does. The related column must be
        // qualified against the related table, not the main one (regression: the
        // sort column was being qualified to `posts.name`, producing invalid SQL).
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'user' => SortableFilter::make()
                ->setColumn('name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class)),
        ];

        $zoro = User::factory()->create(['name' => 'Zoro']);
        $alisa = User::factory()->create(['name' => 'Alisa']);

        Post::factory()->create(['title' => 'Z', 'user_id' => $zoro->id]);
        Post::factory()->create(['title' => 'A', 'user_id' => $alisa->id]);

        $this->getJson(PostRepository::route(query: ['related' => 'user', 'sort' => 'user']))
            ->assertOk()
            ->assertJsonPath('data.0.relationships.user.attributes.name', 'Alisa');

        $this->getJson(PostRepository::route(query: ['related' => 'user', 'sort' => '-user']))
            ->assertOk()
            ->assertJsonPath('data.0.relationships.user.attributes.name', 'Zoro');
    }

    public function test_sort_qualifies_column_when_searchable_belongs_to_joins_exist(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class)->searchable([
                'users.name',
            ]),
        ];

        PostRepository::$sort = [
            'id',
        ];

        $user = User::factory()->create(['name' => 'John']);

        Post::factory()
            ->count(2)
            ->sequence(
                ['title' => 'First Post'],
                ['title' => 'Second Post'],
            )
            ->create(['user_id' => $user->id]);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: [
            'sort' => 'id',
            'search' => 'John',
        ]))->assertOk();

        $queries = DB::getQueryLog();

        $orderQuery = collect($queries)->first(
            static fn (array $query) => str_contains($query['query'], 'order by')
        );

        $this->assertNotNull($orderQuery, 'Expected a query with ORDER BY clause');
        $this->assertStringContainsString(
            'order by "posts"."id"',
            $orderQuery['query'],
            'Sort column should be qualified with table name to prevent ambiguity when joins are present'
        );
    }
}
