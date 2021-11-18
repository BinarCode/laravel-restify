<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class BelongsToFilterTest extends IntegrationTest
{
    public function test_can_filter_using_belongs_to_field(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user',  UserRepository::class),
        ];

        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()->setColumn('users.name')->usingRelation(
                BelongsTo::make('user',  UserRepository::class),
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
            ->getJson(PostRepository::uriKey().'?related=user&sort=-users.attributes.name&perPage=5')
            ->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            ->getJson(PostRepository::uriKey().'?related=user&sort=-users.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );

        $json = $this
            ->getJson(PostRepository::uriKey().'?related=user&sort=users.attributes.name&perPage=5')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            ->getJson(PostRepository::uriKey().'?related=user&sort=users.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );
    }

    public function test_can_filter_self_defined_belongs_to_field(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user',  UserRepository::class)->sortable('name'),
        ];

        PostRepository::$sort = [];

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
            // plural `users.attributes`
            ->getJson(PostRepository::uriKey().'?related=user&sort=-users.attributes.name&perPage=5')
            ->json();


        $this->assertSame(
            'Zez',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            // singular `user.attributes`
            ->getJson(PostRepository::uriKey().'?related=user&sort=-user.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );

        $json = $this
            ->getJson(PostRepository::uriKey().'?related=user&sort=user.attributes.name&perPage=5')
            ->json();

        $this->assertSame(
            'Ame',
            data_get($json, 'data.0.relationships.user.attributes.name')
        );

        $json = $this
            ->getJson(PostRepository::uriKey().'?related=user&sort=user.attributes.name&perPage=6&page=4')
            ->json();

        $this->assertSame(
            'Zez',
            data_get($json, 'data.5.relationships.user.attributes.name')
        );
    }
}
