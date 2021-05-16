<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class MatchFilterTest extends IntegrationTest
{
    public function test_match_definitions_includes_title(): void
    {
        PostRepository::$match = [
            'user_id' => MatchFilter::make()
                ->setType('int')
                ->setRelatedRepositoryKey(UserRepository::uriKey()),

            'title' => 'string',
        ];

        $this->getJson('posts/filters?only=matches')
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
}
