<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
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
            'user' => BelongsTo::make('user', 'user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()->setColumn('users.name')->usingBelongsTo(
                BelongsTo::make('user', 'user', UserRepository::class),
            ),
        ];

        $randomUser = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Post::factory(22)->create([
            'user_id' => $randomUser->id,
        ]);

        Post::factory()->create([
            'user_id' => User::factory()->create([
                'name' => 'Zez',
            ]),
        ]);

        Post::factory()->create([
            'user_id' => User::factory()->create([
                'name' => 'Ame',
            ]),
        ]);

        $json = $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::uriKey().'?related=user&sort=-users.attributes.name&perPage=5')
            ->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::uriKey().'?related=user&sort=-users.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );

        $json = $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::uriKey().'?related=user&sort=users.attributes.name&perPage=5')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::uriKey().'?related=user&sort=users.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );
    }
}
