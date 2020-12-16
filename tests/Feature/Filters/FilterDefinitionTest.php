<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class FilterDefinitionTest extends IntegrationTest
{
    public function test_filters_can_have_definition()
    {
        PostRepository::$match = [
            'title' => 'string',
            'user_id' => MatchFilter::make()
                ->setType('int')
                ->setRelatedRepositoryKey(UserRepository::uriKey())
        ];

        PostRepository::$search = [
            'title' => SearchableFilter::make()->setType('string'),
        ];

        PostRepository::$sort = [
            'id' => SortableFilter::make()->setType('int'),
            'title',
        ];

        $this->getJson('posts/filters?only=matches,searchables,sortables')
            ->assertJsonFragment([
                'related_repository_key' => 'users',
            ]);
    }
}
