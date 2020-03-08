<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Tests\Fixtures\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\PostPolicy;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Gate;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryUpdateControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_basic_update_works()
    {
        $post = factory(Post::class)->create(['user_id' => 1]);

        $this->withoutExceptionHandling()->patch('/restify-api/posts/' . $post->id, [
            'title' => 'Updated title',
        ])
            ->assertStatus(200);

        $updatedPost = Post::find($post->id);

        $this->assertEquals($updatedPost->title, 'Updated title');
    }

    public function test_put_works()
    {
        $post = factory(Post::class)->create(['user_id' => 1]);

        $this->withoutExceptionHandling()->put('/restify-api/posts/' . $post->id, [
            'title' => 'Updated title',
        ])
            ->assertStatus(200);

        $updatedPost = Post::find($post->id);

        $this->assertEquals($updatedPost->title, 'Updated title');
    }

    public function test_unathorized_to_update()
    {
        $this->app->bind(ExceptionHandler::class, RestifyHandler::class);

        Gate::policy(Post::class, PostPolicy::class);

        $post = factory(Post::class)->create(['user_id' => 1]);

        $_SERVER['restify.post.updateable'] = false;

        $this->patch('/restify-api/posts/' . $post->id, [
            'title' => 'Updated title',
        ])->assertStatus(403)
            ->assertJson([
                'errors' => ['This action is unauthorized.'],
            ]);
    }
}
