<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;

class MatchFilterTest extends IntegrationTestCase
{
    public function test_matchable_filter_has_key(): void
    {
        $filter = new class extends MatchFilter
        {
            public ?string $column = 'approved_at';
        };

        tap(
            AssertableJson::fromArray($filter->jsonSerialize()),
            function (AssertableJson $json) {
                $json
                    ->where('key', 'matches')
                    ->where('title', 'Approved At')
                    ->where('column', 'approved_at')
                    ->etc();
            }
        );
    }

    public function test_match_definitions_includes_title(): void
    {
        PostRepository::$match = [
            'user_id' => MatchFilter::make()
                ->setType('int')
                ->setRelatedRepositoryKey(UserRepository::uriKey()),

            'title' => 'string',
        ];

        $this->getJson(PostRepository::route('filters', query: [
            'only' => 'matches',
        ]))
            ->assertJsonStructure([
                'data' => [
                    [
                        'repository' => [
                            'key',
                            'url',
                            'display_key',
                            'label',
                        ],
                    ],
                ],
            ]);
    }

    public function test_match_definition_hit_filter_method(): void
    {
        User::factory(4)->create();

        UserRepository::$match = [
            'id' => MatchFilter::make()->setType(RestifySearchable::MATCH_ARRAY),
        ];

        $this->getJson(UserRepository::route(query: [
            '-id' => '1,2,3',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        UserRepository::$match = [
            'id' => MatchFilter::make()->setType(RestifySearchable::MATCH_ARRAY),
        ];

        $this->getJson(UserRepository::route(query: [
            'id' => '1,2,3',
        ]))
            ->assertJsonCount(3, 'data');
    }

    public function test_match_partially(): void
    {
        User::factory(2)->create([
            'name' => 'John Doe',
        ]);

        UserRepository::$match = [
            'name' => MatchFilter::make()->setType(RestifySearchable::MATCH_TEXT)->strict(),
        ];

        $this->getJson(UserRepository::route(query: ['name' => 'John']))->assertJsonCount(0, 'data');

        UserRepository::$match = [
            'name' => MatchFilter::make()->setType(RestifySearchable::MATCH_TEXT)->strict(),
        ];

        $this->getJson(UserRepository::route(query: ['-name' => 'John']))->assertJsonCount(2, 'data');

        UserRepository::$match = [
            'name' => MatchFilter::make()->setType(RestifySearchable::MATCH_TEXT)->partial(),
        ];

        $this->getJson(UserRepository::route(query: ['name' => 'John']))->assertJsonCount(2, 'data');

        UserRepository::$match = [
            'name' => MatchFilter::make()->setType(RestifySearchable::MATCH_TEXT)->partial(),
        ];
        $this->getJson(UserRepository::route(query: ['-name' => 'John']))->assertJsonCount(0, 'data');
    }

    public function test_can_match_range(): void
    {
        User::factory(4)->create();

        UserRepository::$match = [
            'id' => RestifySearchable::MATCH_BETWEEN,
        ];

        $this->getJson(UserRepository::route(query: ['id' => '1,3']))
            ->assertJsonCount(3, 'data');
    }

    public function test_can_match_using_json_api_recommendation(): void
    {
        User::factory(4)->create();

        UserRepository::$match = [
            'id' => RestifySearchable::MATCH_ARRAY,
        ];

        $this->getJson(UserRepository::route(query: ['filter[id]' => '1,2,3']))
            ->assertJsonCount(3, 'data');

        $this->getJson(UserRepository::route(query: ['filter[-id]' => '1,2,3']))
            ->assertJsonCount(1, 'data');
    }

    public function test_can_match_array(): void
    {
        User::factory(4)->create();

        UserRepository::$match = [
            'id' => RestifySearchable::MATCH_ARRAY,
        ];

        $this->getJson(UserRepository::route(query: ['id' => '1,2,3']))
            ->assertJsonCount(3, 'data');

        $this->getJson(UserRepository::route(query: ['-id' => '1,2,3']))
            ->assertJsonCount(1, 'data');
    }

    public function test_can_match_date(): void
    {
        User::factory(2)->create([
            'created_at' => null,
        ]);

        User::factory(3)->create([
            'created_at' => '01-12-2020',
        ]);

        UserRepository::$match = [
            'created_at' => RestifySearchable::MATCH_DATETIME,
        ];

        $this->getJson(UserRepository::route(query: ['created_at' => 'null']))->assertJsonCount(2, 'data');

        $this->getJson(UserRepository::route(query: ['created_at' => '2020-12-01']))->assertJsonCount(3, 'data');
    }

    public function test_can_match_datetime_interval(): void
    {
        User::factory()->state([
            'created_at' => now()->subMonth(),
        ])->create();

        User::factory()->state([
            'created_at' => now()->subWeek(),
        ])->create();

        User::factory()->state([
            'created_at' => now()->addMonth(),
        ])->create();

        UserRepository::$match = [
            'created_at' => RestifySearchable::MATCH_DATETIME,
        ];

        $now = now()->toISOString();
        $twoMonthsAgo = now()->subMonths(2)->toISOString();

        $this->getJson(UserRepository::route(query: ['created_at' => "$twoMonthsAgo,$now"]))
            ->assertJsonCount(2, 'data');

        $this->getJson(UserRepository::route(query: ['-created_at' => "$twoMonthsAgo,$now"]))
            ->assertJsonCount(1, 'data');
    }

    public function test_can_match_closure(): void
    {
        User::factory(4)->state([
            'active' => false,
        ])->create();

        User::factory()->state([
            'active' => true,
        ])->create();

        UserRepository::$match = [
            'is_active' => function ($request, $query) {
                $this->assertInstanceOf(Request::class, $request);
                $this->assertInstanceOf(Builder::class, $query);

                return $query->where('active', true);
            },
        ];

        $this
            ->getJson(UserRepository::route(query: ['is_active' => true]))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 1)
                    ->etc()
            );
    }
}
