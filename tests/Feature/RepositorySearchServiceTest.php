<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class RepositorySearchServiceTest extends IntegrationTest
{
    /** * @var RepositorySearchService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_can_match_date()
    {
        factory(User::class)->create([
            'created_at' => null,
        ]);

        factory(User::class)->create([
            'created_at' => '01-12-2020',
        ]);

        UserRepository::$match = [
            'created_at' => RestifySearchable::MATCH_DATETIME,
        ];

        $this->get('users?created_at=null')->assertJsonCount(1, 'data');

        $this->get('users?created_at=2020-12-01')->assertJsonCount(1, 'data');
    }

    public function test_can_match_array()
    {
        factory(User::class, 4)->create();

        UserRepository::$match = [
            'id' => RestifySearchable::MATCH_ARRAY,
        ];

        $this->getJson('users?id=1,2,3')
            ->assertJsonCount(3, 'data');

        $this->getJson('users?-id=1,2,3')
            ->assertJsonCount(1, 'data');
    }

    public function test_can_match_datetime_interval()
    {
        $user = factory(User::class)->create();

        $user->forceFill([
            'created_at' => now()->subMonth(),
        ]);
        $user->save();
        $user = factory(User::class)->create();

        $user->forceFill([
            'created_at' => now()->subWeek(),
        ]);
        $user->save();

        $user = factory(User::class)->create();

        $user->forceFill([
            'created_at' => now()->addMonth(),
        ]);
        $user->save();

        UserRepository::$match = [
            'created_at' => RestifySearchable::MATCH_DATETIME_INTERVAL,
        ];

        $twoMonthsAgo = now()->subMonths(2)->toISOString();
        $now = now()->toISOString();
        $this->getJson("users?created_at={$twoMonthsAgo},{$now}")
            ->assertJsonCount(2, 'data');

        $this->getJson("users?-created_at={$twoMonthsAgo},{$now}")
            ->assertJsonCount(1, 'data');
    }

    public function test_match_definition_hit_filter_method()
    {
        factory(User::class, 4)->create();

        UserRepository::$match = [
            'id' => MatchFilter::make()->setType(RestifySearchable::MATCH_ARRAY),
        ];

        $this->getJson('users?-id=1,2,3')
            ->assertJsonCount(1, 'data');

        UserRepository::$match = [
            'id' => MatchFilter::make()->setType(RestifySearchable::MATCH_ARRAY),
        ];

        $this->getJson('users?id=1,2,3')
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_using_filter_searchable_definition()
    {
        factory(User::class, 4)->create([
            'name' => 'John Doe',
        ]);

        factory(User::class, 4)->create([
            'name' => 'wew',
        ]);

        UserRepository::$search = [
            'name' => SearchableFilter::make(),
        ];

        $this->getJson('users?search=John')
            ->assertJsonCount(4, 'data');
    }

    public function test_can_order_using_filter_sortable_definition()
    {
        factory(User::class)->create([
            'name' => 'Zoro',
        ]);

        factory(User::class)->create([
            'name' => 'Alisa',
        ]);

        UserRepository::$sort = [
            'name' => SortableFilter::make()->setColumn('name'),
        ];

        $this->assertSame('Alisa', $this->getJson('users?sort=name')
            ->json('data.0.attributes.name'));

        $this->assertSame('Zoro', $this->getJson('users?sort=name')
            ->json('data.1.attributes.name'));
        $this->assertSame('Zoro', $this->getJson('users?sort=-name')
            ->json('data.0.attributes.name'));
        $this->assertSame('Alisa', $this->getJson('users?sort=-name')
            ->json('data.1.attributes.name'));
    }

    public function test_can_match_closure()
    {
        factory(User::class, 4)->create();

        UserRepository::$match = [
            'is_active' => function ($request, $query) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Builder::class, $query);
            },
        ];

        $this->getJson('users?is_active=true');
    }
}
