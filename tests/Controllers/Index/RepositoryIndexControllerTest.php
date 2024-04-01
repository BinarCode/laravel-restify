<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;

class RepositoryIndexControllerTest extends IntegrationTestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_paginate(): void
    {
        PostFactory::many(15);

        PostRepository::$defaultPerPage = 5;

        $this->getJson(PostRepository::route())
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 5)
                    ->etc()
            );

        $this->getJson(PostRepository::route(query: [
            'perPage' => 10,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 10)
                ->etc()
        );

        $this->getJson(PostRepository::route(query: [
            'page[size]' => 10,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 10)
                ->etc()
        );

        $this->getJson(PostRepository::route(query: [
            'perPage' => 10,
            'page' => '2',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 5)
                ->etc()
        );

        $this->getJson(PostRepository::route(query: [
            'page[size]' => 10,
            'page[number]' => 2,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 5)
                ->etc()
        );
    }

    #[Test]
    public function it_can_search_using_query(): void
    {
        PostFactory::one([
            'title' => 'Title with code word',
        ]);

        PostFactory::one([
            'title' => 'Another title with code inner',
        ]);

        PostFactory::one([
            'title' => 'A title with no key word',
        ]);

        PostFactory::one([
            'title' => 'Lorem ipsum dolor',
        ]);

        PostRepository::$search = ['title'];

        $this->getJson(PostRepository::route(query: [
            'search' => 'code',
        ]))->assertJson(fn (AssertableJson $json) => $json->count('data', 2)->etc());
    }

    #[Test]
    public function it_can_sort_using_query(): void
    {
        PostFactory::one([
            'title' => 'AAA',
        ]);

        PostFactory::one([
            'title' => 'ZZZ',
        ]);

        PostRepository::$sort = [
            'title',
        ];

        $this->getJson(PostRepository::route(query: [
            'sort' => '-title',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.attributes.title', 'ZZZ')
                ->where('data.1.attributes.title', 'AAA')
                ->etc()
        );

        $this->getJson(PostRepository::route(query: [
            'sort' => 'title',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.attributes.title', 'AAA')
                ->where('data.1.attributes.title', 'ZZZ')
                ->etc()
        );
    }

    #[Test]
    public function it_can_return_related_entity(): void
    {
        PostRepository::$related = [
            'user',
        ];

        Post::factory()->for(
            User::factory()->state([
                'name' => $name = 'John Doe',
            ])
        )->create();

        $this->getJson(PostRepository::route(query: [
            'related' => 'user',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.0.relationships.user.name', $name)
                ->etc()
        );
    }

    public function test_index_unmergeable_repository_contains_only_explicitly_defined_fields(): void
    {
        PostFactory::one();

        $response = $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'attributes' => [
                            'user_id',
                            'title',
                            'description',
                        ],
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('image', $response->json('data.0.attributes'));
    }

    public function test_index_mergeable_repository_contains_model_attributes_and_local_fields(): void
    {
        Restify::repositories([
            PostMergeableRepository::class,
        ]);

        $this->getJson(PostMergeableRepository::route(
            $this->mockPost()->id
        ))->assertJsonStructure([
            'data' => [
                'attributes' => [
                    'user_id',
                    'title',
                    'description',
                    'image',
                ],
            ],
        ]);
    }

    public function test_can_add_custom_index_main_meta_attributes(): void
    {
        Post::factory()->create([
            'title' => 'Post Title',
        ]);

        $response = $this->getJson(PostRepository::route())
            ->assertJsonStructure([
                'meta' => [
                    'postKey',
                ],
            ]);

        $this->assertEquals('Custom Meta Value', $response->json('meta.postKey'));
        $this->assertEquals('Post Title', $response->json('meta.first_title'));
    }
}
