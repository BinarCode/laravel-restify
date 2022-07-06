<?php

namespace Binaryk\LaravelRestify\Tests\Feature\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class BelongsToFilterTest extends IntegrationTest
{
    public function test_can_filter_using_belongs_to_field(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class),
        ];

        PostRepository::$sort = [
            'users.attributes.name' => SortableFilter::make()->setColumn('users.name')->usingRelation(
                BelongsTo::make('user', UserRepository::class),
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

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => '-users.attributes.name',
                'perPage' => 5,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user.attributes.name', 'Zez')
                ->etc()
            );

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => '-users.attributes.name',
                'perPage' => 6,
                'page' => 4,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.5.relationships.user.attributes.name', 'Ame')
                ->etc()
            );

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => 'users.attributes.name',
                'perPage' => 5,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user.attributes.name', 'Ame')
                ->etc()
            );

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => 'users.attributes.name',
                'perPage' => 6,
                'page' => 4,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.5.relationships.user.attributes.name', 'Zez')
                ->etc()
            );
    }

    public function test_can_filter_self_defined_belongs_to_field(): void
    {
        PostRepository::$related = [
            'user' => BelongsTo::make('user', UserRepository::class)->sortable('name'),
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

        $this
            // plural `users.attributes`
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => '-users.attributes.name',
                'perPage' => 5,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user.attributes.name', 'Zez')
                ->etc()
            );

        $this
            // singular `user.attributes`
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => '-users.attributes.name',
                'perPage' => 6,
                'page' => 4,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.5.relationships.user.attributes.name', 'Ame')
                ->etc()
            );

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => 'users.attributes.name',
                'perPage' => 5,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.0.relationships.user.attributes.name', 'Ame')
                ->etc()
            );

        $this
            ->getJson(PostRepository::route(query: [
                'related' => 'user',
                'sort' => 'users.attributes.name',
                'perPage' => 6,
                'page' => 4,
            ]))->assertJson(fn(AssertableJson $json) => $json
                ->where('data.5.relationships.user.attributes.name', 'Zez')
                ->etc()
            );
    }
}
