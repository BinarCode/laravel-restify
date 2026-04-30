<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;

class SortableJoinStrategyTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => false]);

        PostRepository::$search = ['id', 'title'];
        PostRepository::$sort = ['title', 'is_active'];
        PostRepository::$related = [];
        UserRepository::$search = ['id', 'name'];
        UserRepository::$sort = ['id'];
        UserRepository::$related = ['posts'];

        parent::tearDown();
    }

    public function test_belongs_to_sort_uses_subquery_when_flag_off(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => false]);

        $this->seedPostsWithUsers();

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['sort' => 'users.attributes.name']))
            ->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertStringContainsStringIgnoringCase('order by (select', $sql);
        $this->assertStringNotContainsStringIgnoringCase('left join "users"', $sql);
    }

    public function test_belongs_to_sort_uses_left_join_when_flag_on(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => true]);

        $this->seedPostsWithUsers();

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['sort' => 'users.attributes.name']))
            ->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertStringContainsStringIgnoringCase('left join "users"', $sql);
        $this->assertStringContainsStringIgnoringCase('order by "users"."name"', $sql);
        $this->assertStringNotContainsStringIgnoringCase('order by (select', $sql);
    }

    public function test_use_join_override_beats_config_false(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => false]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()
                ->setColumn('users.name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class))
                ->useJoin(),
        ];

        $this->seedPostsWithUsers(seedFixtures: false);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['sort' => 'users.attributes.name']))
            ->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertStringContainsStringIgnoringCase('left join "users"', $sql);
        $this->assertStringNotContainsStringIgnoringCase('order by (select', $sql);
    }

    public function test_use_subquery_override_beats_config_true(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => true]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()
                ->setColumn('users.name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class))
                ->useSubquery(),
        ];

        $this->seedPostsWithUsers(seedFixtures: false);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: ['sort' => 'users.attributes.name']))
            ->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertStringContainsStringIgnoringCase('order by (select', $sql);
        $this->assertStringNotContainsStringIgnoringCase('left join "users"', $sql);
    }

    public function test_join_is_deduped_when_search_already_joined_same_table(): void
    {
        config([
            'restify.sort.use_joins_for_belongs_to' => true,
            'restify.search.use_joins_for_belongs_to' => true,
        ]);

        $john = User::factory()->create(['name' => 'John Doe']);
        Post::factory(2)->create(['user_id' => $john->id]);
        Post::factory(1)->create([
            'user_id' => User::factory()->create(['name' => 'Other'])->id,
        ]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class)->searchable(['users.name']),
        ];
        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()
                ->setColumn('users.name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class)),
        ];

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: [
            'search' => 'John',
            'sort' => 'users.attributes.name',
        ]))->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertSame(1, substr_count(strtolower($sql), 'left join "users"'));
    }

    public function test_multiple_sortables_on_same_relation_share_one_join(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => true]);

        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];
        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()
                ->setColumn('users.name')
                ->usingRelation(BelongsTo::make('user', UserRepository::class)),
            'users.attributes.email' => SortableFilter::make()
                ->setColumn('users.email')
                ->usingRelation(BelongsTo::make('user', UserRepository::class)),
        ];

        Post::factory(3)->create([
            'user_id' => User::factory()->create()->id,
        ]);

        DB::enableQueryLog();

        $this->getJson(PostRepository::route(query: [
            'sort' => 'users.attributes.name,users.attributes.email',
        ]))->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertSame(1, substr_count(strtolower($sql), 'left join "users"'));
        $this->assertStringContainsStringIgnoringCase('order by "users"."name"', $sql);
        $this->assertStringContainsStringIgnoringCase('"users"."email"', $sql);
    }

    public function test_has_one_sort_uses_left_join_when_flag_on(): void
    {
        config(['restify.sort.use_joins_for_belongs_to' => true]);

        UserRepository::$related = [
            'post' => HasOne::make('post', PostRepository::class),
        ];
        UserRepository::$sort = [
            'posts.attributes.title' => SortableFilter::make()
                ->setColumn('posts.title')
                ->usingRelation(HasOne::make('post', PostRepository::class)),
        ];

        $u1 = User::factory()->create(['name' => 'A']);
        $u2 = User::factory()->create(['name' => 'B']);
        Post::factory()->create(['user_id' => $u1->id, 'title' => 'Zeta']);
        Post::factory()->create(['user_id' => $u2->id, 'title' => 'Alpha']);

        DB::enableQueryLog();

        $this->getJson(UserRepository::route(query: ['sort' => 'posts.attributes.title']))
            ->assertOk();

        $sql = $this->lastIndexQuery();

        $this->assertStringContainsStringIgnoringCase('left join "posts"', $sql);
        $this->assertStringContainsStringIgnoringCase('order by "posts"."title"', $sql);
        $this->assertStringNotContainsStringIgnoringCase('order by (select', $sql);
    }

    public function test_subquery_and_join_strategies_produce_identical_id_order(): void
    {
        $this->seedPostsWithUsers(count: 30);

        config(['restify.sort.use_joins_for_belongs_to' => false]);
        $idsSubquery = $this->getJson(PostRepository::route(query: [
            'sort' => 'users.attributes.name',
            'perPage' => 50,
        ]))->json('data.*.id');

        config(['restify.sort.use_joins_for_belongs_to' => true]);
        $idsJoin = $this->getJson(PostRepository::route(query: [
            'sort' => 'users.attributes.name',
            'perPage' => 50,
        ]))->json('data.*.id');

        $this->assertSame($idsSubquery, $idsJoin);
    }

    private function seedPostsWithUsers(int $count = 6, bool $seedFixtures = true): void
    {
        if ($seedFixtures) {
            PostRepository::$related = [
                'user' => BelongsTo::make('user', UserRepository::class),
            ];
            PostRepository::$sort = [
                'users.attributes.name' => SortableFilter::make()
                    ->setColumn('users.name')
                    ->usingRelation(BelongsTo::make('user', UserRepository::class)),
            ];
        }

        $names = ['Alice', 'Bob', 'Carol', 'Dan', 'Eve', 'Frank', 'Grace', 'Hank', 'Ivy', 'Jack'];

        for ($i = 0; $i < $count; $i++) {
            $user = User::factory()->create([
                'name' => $names[$i % count($names)].'-'.$i,
            ]);
            Post::factory()->create(['user_id' => $user->id]);
        }
    }

    private function lastIndexQuery(): string
    {
        $log = collect(DB::getQueryLog())
            ->reverse()
            ->first(static fn (array $entry): bool => str_contains(strtolower($entry['query']), 'from "posts"')
                || str_contains(strtolower($entry['query']), 'from "users"'));

        $this->assertNotNull($log, 'no posts/users index query captured');

        $sql = $log['query'];

        foreach ($log['bindings'] as $binding) {
            $sql = preg_replace('/\?/', is_string($binding) ? "'".$binding."'" : (string) $binding, $sql, 1);
        }

        return $sql;
    }
}
