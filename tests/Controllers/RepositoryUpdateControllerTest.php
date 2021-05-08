<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryUpdateControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_basic_update_works()
    {
        $post = factory(Post::class)->create();

        $this->patch('posts/'.$post->id, [
            'title' => 'Updated title',
        ])->assertOk();

        $this->assertEquals('Updated title', Post::find($post->id)->title);
    }

    public function test_put_works()
    {
        $post = factory(Post::class)->create();

        $this->withoutExceptionHandling()->put('posts/'.$post->id, [
            'title' => 'Updated title',
        ])->assertOk();

        $this->assertEquals('Updated title', Post::find($post->id)->title);
    }

    public function test_unauthorized_to_update(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $post = factory(Post::class)->create();

        $_SERVER['restify.post.update'] = false;

        $this->patchJson('posts/'.$post->id, [
            'title' => 'Updated title',
        ])->assertStatus(403);
    }

    public function test_do_not_update_fields_without_permission(): void
    {
        $post = factory(Post::class)->create(['user_id' => 1, 'title' => 'Title']);

        $_SERVER['posts.authorizable.title'] = false;

        $response = $this->putJson('post-with-unathorized-fields/'.$post->id, [
            'title' => 'Updated title',
            'user_id' => 2,
        ])
            ->assertOk();

        $this->assertEquals('Title', $response->json('data.attributes.title'));
        $this->assertEquals(2, $response->json('data.attributes.user_id'));
    }

    public function test_will_not_update_readonly_fields(): void
    {
        $user = $this->mockUsers()->first();

        $post = factory(Post::class)->create(['image' => null]);

        $r = $this->putJson('posts-unauthorized-fields/'.$post->id, [
            'user_id' => $user->id,
            'image' => 'avatar.png',
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->assertOk();

        $this->assertNull($r->json('data.attributes.image'));
    }

    public function test_updating_repository_log_action()
    {
        $this->authenticate();

        $post = factory(Post::class)->create([
            'title' => 'Original',
        ]);

        $this->postJson("posts/$post->id", $data = [
            'title' => 'Title changed',
        ])->assertSuccessful();

        $this->assertDatabaseHas('action_logs', [
            'user_id' => $this->authenticatedAs->getAuthIdentifier(),
            'name' => ActionLog::ACTION_UPDATED,
            'actionable_type' => Post::class,
            'actionable_id' => (string) $post->id,
        ]);

        $log = ActionLog::latest()->first();

        $this->assertSame($data, $log->changes);
        $this->assertSame(['title' => 'Original'], $log->original);
    }
}
