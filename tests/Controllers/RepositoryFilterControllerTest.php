<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class RepositoryFilterControllerTest extends IntegrationTestCase
{
    public function test_available_filters_contains_matches_sortables_searches(): void
    {
        PostRepository::$match = [
            'title' => 'text',
        ];

        PostRepository::$sort = [
            'title',
        ];

        PostRepository::$search = [
            'id',
            'title',
        ];

        $this->withoutExceptionHandling();
        $this->getJson(PostRepository::route('filters', query: [
            'include' => 'matches,sortables,searchables',
        ]))
            // 5 custom filters
            // 1 match filter
            // 1 sort
            // 2 searchable
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('data.0.rules.is_active', 'bool')
                    ->where('data.5.type', 'text')
                    ->where('data.5.column', 'title')
                    ->where('data.6.type', 'value')
                    ->where('data.6.column', 'title')
                    ->where('data.7.type', 'value')
                    ->where('data.7.column', 'id')
                    ->etc()
            )
            ->assertJsonCount(9, 'data');
    }

    public function test_available_filters_returns_only_matches_sortables_searches(): void
    {
        PostRepository::$match = [
            'title' => 'text',
        ];

        PostRepository::$sort = [
            'title' => SortableFilter::make()->setColumn('posts.title'),
        ];

        PostRepository::$search = [
            'id',
            'title',
        ];

        $this->getJson(PostRepository::route('filters', query: [
            'only' => 'matches,sortables,searchables',
        ]))->assertJsonCount(4, 'data');

        $this->getJson(PostRepository::route('filters', query: ['only' => 'matches']))
            ->assertJsonCount(1, 'data');

        $this->getJson(PostRepository::route('filters', query: ['only' => 'sortables']))->assertJsonCount(1, 'data');

        $this->getJson(PostRepository::route('filters', query: ['only' => 'searchables']))
            ->assertJsonCount(2, 'data');
    }

    public function test_filters_will_render_placeholder(): void
    {
        PostRepository::$match = [
            'title' => MatchFilter::make()
                ->setDescription('Sort by title')
                ->setPlaceholder('-title')
                ->setType('string'),
        ];

        $this->getJson(PostRepository::route('filters', query: [
            'only' => 'matches',
        ]))
            ->assertJson(function (AssertableJson $json) {
                $json
                    ->where('data.0.placeholder', '-title')
                    ->where('data.0.description', 'Sort by title')
                    ->where('data.0.type', 'string')
                    ->where('data.0.column', 'title')
                    ->etc();
            });
    }
}
