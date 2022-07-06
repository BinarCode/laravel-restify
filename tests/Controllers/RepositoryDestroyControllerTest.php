<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryDestroyControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_destroy_works(): void
    {
        $post = Post::factory()->create(['user_id' => 1]);

        $this->assertInstanceOf(Post::class, Post::find($post->id));

        $_SERVER['restify.post.delete'] = true;

        $this->deleteJson('posts/'.$post->id, [
            'title' => 'Updated title',
        ])
            ->assertStatus(204);

        $this->assertNull(Post::find($post->id));
    }

    public function test_unauthorized_to_destroy(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $post = Post::factory()->create(['user_id' => 1]);

        $_SERVER['restify.post.delete'] = false;

        $this->deleteJson(PostRepository::route($post->id))->assertStatus(403);

        $this->assertInstanceOf(Post::class, $post->refresh());
    }

    public function test_destroying_repository_log_action(): void
    {
        $this->authenticate();

        $post = Post::factory()->create([
            'title' => 'Original title',
        ]);

        $_SERVER['restify.post.delete'] = true;

        $this->deleteJson("posts/$post->id")->assertNoContent();

        $this->assertDatabaseHas('action_logs', [
            'user_id' => $this->authenticatedAs->getAuthIdentifier(),
            'name' => ActionLog::ACTION_DELETED,
            'actionable_type' => Post::class,
            'actionable_id' => $post->getKey(),
        ]);

        $log = ActionLog::latest()->first();

        $this->assertSame($post->title, data_get($log->original, 'title'));
    }
}
