<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\PostPolicy;
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
        $this->postJson('/restify-api/posts', [])
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
        $_SERVER['restify.post.creatable'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->postJson('/restify-api/posts', [
            'title' => 'Title',
            'description' => 'Title',
        ])->assertStatus(403)
            ->assertJson(['errors' => ['Unauthorized to store.']]);
    }

    public function test_success_storing()
    {
        $user = $this->mockUsers()->first();
        $r = $this->postJson('/restify-api/posts', [
            'user_id' => $user->id,
            'title' => 'Some post title',
        ])->assertStatus(201)
            ->assertHeader('Location', '/restify-api/posts/1');

        $this->assertEquals('Some post title', $r->json('data.attributes.title'));
        $this->assertEquals(1, $r->json('data.attributes.user_id'));
        $this->assertEquals(1, $r->json('data.id'));
        $this->assertEquals('posts', $r->json('data.type'));
    }

    public function test_will_store_only_defined_fields_from_fieldsForStore()
    {
        $user = $this->mockUsers()->first();
        $r = $this->postJson('/restify-api/posts', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->assertStatus(201)
            ->assertHeader('Location', '/restify-api/posts/1');

        $this->assertEquals('Some post title', $r->json('data.attributes.title'));
        $this->assertNull($r->json('data.attributes.description'));
    }

    public function test_will_store_fillable_attributes_for_mergeable_repository()
    {
        $user = $this->mockUsers()->first();
        $r = $this->postJson('/restify-api/posts-mergeable', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            // The description is automatically filled based on fillable and Mergeable contract
            'description' => 'A very short description',
        ])
            ->assertStatus(201)
            ->assertHeader('Location', '/restify-api/posts-mergeable/1');

        $this->assertEquals('Some post title', $r->json('data.attributes.title'));
        $this->assertEquals('A very short description', $r->json('data.attributes.description'));
    }

    public function test_will_not_store_unauthorized_fields()
    {
        $user = $this->mockUsers()->first();
        $r = $this->postJson('/restify-api/posts-unauthorized-fields', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->dump()
            ->assertStatus(201);

        $_SERVER['posts.description.authorized'] = false;

        $this->assertEquals('Some post title', $r->json('data.attributes.title'));
        $this->assertNull($r->json('data.attributes.description'));
    }

    public function test_will_not_store_readonly_fields()
    {
        $user = $this->mockUsers()->first();
        $r = $this->postJson('/restify-api/posts-unauthorized-fields', [
            'user_id' => $user->id,
            'image' => 'avatar.png',
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->dump()
            ->assertStatus(201);

        $this->assertNull($r->json('data.attributes.image'));
    }
}
