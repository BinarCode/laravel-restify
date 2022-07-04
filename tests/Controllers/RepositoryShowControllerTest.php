<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class RepositoryShowControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
        $this->mockPost();
    }

    public function test_basic_show(): void
    {
        $this->getJson(PostRepository::route(1))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);
    }

    public function test_show_will_authorize_fields(): void
    {
        $_SERVER['postAuthorize.can.see.title'] = false;

        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title')->canSee(fn () => $_SERVER['postAuthorize.can.see.title']),

                field('description')->hidden(),
            ]);

        $this->getJson(PostRepository::route(1))
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->missing('data.attributes.title')
                ->etc()
            );

        $_SERVER['postAuthorize.can.see.title'] = true;

        $this->getJson(PostRepository::route(1))
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->has('data.attributes.title')
                ->etc()
            );
    }

    public function test_show_will_take_into_consideration_show_callback(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title')->showCallback(fn ($value) => strtoupper($value)),
            ]);

        $this->getJson(PostRepository::route(
            $this->mockPost([
                'title' => 'wew',
            ])->id
        ))
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->where('data.attributes.title', 'WEW')
            );
    }

    public function test_show_merge_able_repository_contains_model_attributes_and_local_fields(): void
    {
        Restify::repositories([
            PostMergeableRepository::class,
        ]);

        $this->getJson(PostMergeableRepository::route(
            $this->mockPost()->id
        ))
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'id',
                        'user_id',
                        'title',
                        'image',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_repository_hidden_fields_are_not_visible(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fieldsForShow')
            ->andReturn([
                field('title'),

                field('description')->hidden(),
            ]);

        $this->getJson(PostRepository::route(
            $this->mockPosts()->first()->id
        ))
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->missing('data.attributes.description')
                ->has('data.attributes.title')
                ->etc()
            );
    }

    public function test_repository_hidden_fields_could_not_be_updated(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title'),

                field('description')->hidden(),
            ]);

        $this->putJson(PostRepository::route(
            $post = $this->mockPost(['description' => 'Description'])->id
        ), [
            'title' => $title = 'Updated title',
            'description' => 'Updated description',
        ])->assertJson(
            fn (AssertableJson $json) => $json
            ->missing('data.attributes.description')
            ->where('data.attributes.title', $title)
            ->etc()
        );

        $this->assertSame(
            'Description',
            Post::find($post)->description
        );
    }

    public function test_repository_hidden_fields_could_be_updated_through_value(): void
    {
        PostRepository::partialMock()
            ->shouldReceive('fields')
            ->andReturn([
                field('title'),

                // A practical example could be `author_id` which gets Auth::id() as value.
                field('description')->hidden()->value($default = 'Default description'),
            ]);

        $this->putJson(PostRepository::route(
            $post = $this->mockPost()->id
        ), [
            'title' => 'Updated title',
        ])->assertOk();

        $this->assertSame(
            $default,
            Post::find($post)->description
        );
    }
}
