<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostUnauthorizedFieldRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryStoreControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_basic_validation_works()
    {
        $this->postJson('posts', [])
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        'title' => [
                            'This field is required',
                        ],
                    ],
                ],
            ]);
    }

    public function test_unauthorized_store()
    {
        $_SERVER['restify.post.store'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->postJson('posts', [
            'title' => 'Title',
            'description' => 'Title',
        ])->assertStatus(403)
            ->assertJson(['errors' => ['Unauthorized to store.']]);
    }

    public function test_success_storing()
    {
        $user = $this->mockUsers()->first();
        $response = $this->postJson('posts', [
            'user_id' => $user->id,
            'title' => 'Some post title',
        ])->assertStatus(201)
            ->assertHeader('Location', '/posts/1');

        $this->assertEquals('Some post title', $response->json('data.attributes.title'));
        $this->assertEquals(1, $response->json('data.attributes.user_id'));
        $this->assertEquals(1, $response->json('data.id'));
        $this->assertEquals('posts', $response->json('data.type'));
    }

    public function test_will_store_only_defined_fields_from_fieldsForStore()
    {
        $user = $this->mockUsers()->first();
        $response = $this->postJson('posts', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->assertStatus(201)
            ->assertHeader('Location', '/posts/1');

        $this->assertEquals('Some post title', $response->json('data.attributes.title'));
        $this->assertNull($response->json('data.attributes.description'));
    }

    public function test_will_not_store_unauthorized_fields()
    {
        $user = $this->mockUsers()->first();
        $response = $this->postJson('posts-unauthorized-fields', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])->assertStatus(201);

        $_SERVER['posts.description.authorized'] = false;

        $this->assertEquals('Some post title', $response->json('data.attributes.title'));
        $this->assertNull($response->json('data.attributes.description'));
    }

    public function test_will_not_store_readonly_fields()
    {
        $user = $this->mockUsers()->first();
        $response = $this->postJson(PostUnauthorizedFieldRepository::uriKey(), [
            'user_id' => $user->id,
            'image' => 'avatar.png',
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->assertStatus(201);

        $this->assertNull($response->json('data.attributes.image'));
    }
}
