<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

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

        $this->get('/restify-api/posts/1')
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
        $response = $this->getJson('/restify-api/post-authorizes/1');

        $this->assertArrayNotHasKey('title', $response->json('data.attributes'));

        $_SERVER['postAuthorize.can.see.title'] = true;
        $response = $this->getJson('/restify-api/post-authorizes/1');

        $this->assertArrayHasKey('title', $response->json('data.attributes'));
    }

    public function test_show_will_take_into_consideration_show_callback()
    {
        $_SERVER['postAuthorize.can.see.title'] = true;

        factory(Post::class)->create(['title' => 'Eduard',]);

        $response = $this->getJson('/restify-api/post-authorizes/1');

        $this->assertSame('EDUARD', $response->json('data.attributes.title'));
    }

    public function test_show_unmergeable_repository_containes_only_explicitly_defined_fields()
    {
        factory(Post::class)->create(['title' => 'Eduard',]);

        $response = $this->getJson('/restify-api/posts/1')
            ->assertJsonStructure([
                'data' => [
                    'attributes' => [
                        'user_id',
                        'title',
                        'description',
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('id', $response->json('data.attributes'));
        $this->assertArrayNotHasKey('created_at', $response->json('data.attributes'));
    }

    public function test_show_mergeable_repository_containes_model_attributes_and_local_fields()
    {
        factory(Post::class)->create(['title' => 'Eduard',]);

        $this->getJson('/restify-api/posts-mergeable/1')
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
}
