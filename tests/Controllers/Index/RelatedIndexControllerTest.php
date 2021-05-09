<?php

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RelatedIndexControllerTest extends IntegrationTest
{
    public function test_can_list_posts_belongs_to_a_user()
    {
        $this->mockUsers();
        $this->mockPosts(1, 10);

        $this->mockPosts(
            User::factory()->create()->id
        );

        $response = $this->getJson('posts?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertOk();

        $this->assertCount(10, $response->json('data'));
    }

    public function test_can_list_posts_belongs_to_a_user_without_via_relationship_because_get_default_main_repository()
    {
        $this->mockUsers();
        $this->mockPosts(1, 10);

        $this->mockPosts(
            User::factory()->create()->id
        );

        $response = $this->getJson('posts?viaRepository=users&viaRepositoryId=1')
            ->assertStatus(200);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_can_show_post_belongs_to_a_user()
    {
        User::factory()->create();
        User::factory()->create();

        Post::factory()->create([
            'user_id' => 2,
            'title' => 'First Post',
        ]);

        Post::factory()->create([
            'user_id' => 1,
            'title' => 'Second Post',
        ]);

        $this->getJson('posts/1?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertStatus(404);

        $count = $this->getJson('posts/2?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertStatus(200);

        $this->assertCount(1, $count->json());
    }

    public function test_can_store_post_belongs_to_a_user()
    {
        User::factory()->create();

        User::factory()->create();

        $this->postJson('posts?viaRepository=users&viaRepositoryId=1&viaRelationship=posts', [
            'title' => 'Created for the user 1',
        ])
            ->assertStatus(201);

        $belongsFirst = $this->getJson('posts?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertStatus(200);

        $belongsSecond = $this->getJson('posts?viaRepository=users&viaRepositoryId=2&viaRelationship=posts')
            ->assertStatus(200);

        $this->assertCount(1, $belongsFirst->json('data'));
        $this->assertCount(0, $belongsSecond->json('data'));
    }

    public function test_can_update_post_belongs_to_a_user()
    {
        User::factory()->create();
        User::factory()->create();

        Post::factory()->create(['title' => 'Post title', 'user_id' => 1]);

        Post::factory()->create(['title' => 'Post title', 'user_id' => 2]);

        $response = $this->putJson('posts/1?viaRepository=users&viaRepositoryId=1&viaRelationship=posts', [
            'title' => 'Post updated title',
        ])->assertStatus(200);

        $this->putJson('posts/2?viaRepository=users&viaRepositoryId=1&viaRelationship=posts', [
            'title' => 'Post updated title',
        ])->assertStatus(404);

        $this->assertEquals('Post updated title', $response->json('data.attributes.title'));
    }

    public function test_can_destroy_post_belongs_to_a_user()
    {
        User::factory()->create();
        User::factory()->create();

        Post::factory()->create(['title' => 'Post title', 'user_id' => 1]);

        Post::factory()->create(['title' => 'Post title', 'user_id' => 2]);

        $this->deleteJson('posts/1?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')->assertStatus(204);

        $this->deleteJson('posts/2?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')->assertStatus(404);
    }

    public function test_policy_check_before_destroy_post_belongs_to_a_user()
    {
        $_SERVER['restify.post.delete'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        User::factory()->create();

        User::factory()->create();

        Post::factory()->create(['title' => 'Post title', 'user_id' => 1]);

        Post::factory()->create(['title' => 'Post title', 'user_id' => 2]);

        $this->deleteJson('posts/1?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertForbidden();

        $this->deleteJson('posts/2?viaRepository=users&viaRepositoryId=1&viaRelationship=posts')
            ->assertNotFound();
    }
}
