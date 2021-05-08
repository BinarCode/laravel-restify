<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostMergeableRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryShowControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_basic_show()
    {
        factory(Post::class)->create(['user_id' => 1]);

        $this->get('posts/1')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);
    }

    public function test_show_will_authorize_fields()
    {
        factory(Post::class)->create();

        $_SERVER['postAuthorize.can.see.title'] = false;
        $response = $this->getJson('post-authorizes/1');

        $this->assertArrayNotHasKey('title', $response->json('data.attributes'));

        $_SERVER['postAuthorize.can.see.title'] = true;
        $response = $this->getJson('post-authorizes/1');

        $this->assertArrayHasKey('title', $response->json('data.attributes'));
    }

    public function test_show_will_take_into_consideration_show_callback(): void
    {
        $_SERVER['postAuthorize.can.see.title'] = true;

        factory(Post::class)->create(['title' => 'Eduard']);

        $response = $this->getJson('post-authorizes/1');

        $this->assertSame('EDUARD', $response->json('data.attributes.title'));
    }

    public function test_show_merge_able_repository_contains_model_attributes_and_local_fields(): void
    {
        Restify::repositories([
            PostMergeableRepository::class,
        ]);

        $this->getJson(PostMergeableRepository::to(
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

        $this->getJson(PostRepository::to(
            $this->mockPosts()->first()->id
        ))
            ->assertJson(fn(AssertableJson $json) => $json
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

        $this->putJson(PostRepository::to(
            $post = $this->mockPost(['description' => 'Description'])->id
        ), [
            'title' => $title = 'Updated title',
            'description' => 'Updated description',
        ])->assertJson(fn(AssertableJson $json) => $json
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
                field('description')->hidden()->value($default = 'Default description')
            ]);

        $this->putJson(PostRepository::to(
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
