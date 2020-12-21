<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fields\PostWithUserRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class FilterDefinitionTest extends IntegrationTest
{
    public function test_filters_can_have_definition()
    {
        PostRepository::$match = [
            'title' => 'string',
            'user_id' => MatchFilter::make()
                ->setType('int')
                ->setRelatedRepositoryKey(UserRepository::uriKey()),
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
                'key' => 'users',
            ]);
    }

    public function test_match_definitions_includes_title()
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

    public function test_can_filter_using_belongs_to_field()
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', 'user', UserRepository::class)
        ];

        PostRepository::$sort = [
            'users.name' => SortableFilter::make()->usingBelongsTo(
                BelongsTo::make('user', 'user', UserRepository::class),
            )
            /*function (RestifyRequest $request, Builder $builder, $direction) {
                    $builder->join('users', 'posts.user_id', '=', 'users.id')
                        ->select('posts.*')
                        ->orderBy('users.name', $direction);
            }*/,
        ];

        factory(Post::class)->create([
            'user_id' => factory(User::class)->create([
                'name' => 'Zez',
            ])
        ]);

        factory(Post::class)->create([
            'user_id' => factory(User::class)->create([
                'name' => 'Ame',
            ])
        ]);

        $json = $this->getJson(PostRepository::uriKey() . "?related=user&sort=-users.name")->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $this->assertSame(
            'Ame',
            data_get($json, 'data.1.relationships.user.attributes.name')
        );
    }
}
