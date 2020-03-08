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
class RepositoryDestroyControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
        $this->app->bind(ExceptionHandler::class, RestifyHandler::class);
    }

    public function test_destroy_works()
    {
        $post = factory(Post::class)->create(['user_id' => 1]);

        $this->assertInstanceOf(Post::class, Post::find($post->id));

        $this->withoutExceptionHandling()->delete('/restify-api/posts/' . $post->id, [
            'title' => 'Updated title',
        ])
            ->assertStatus(204);

        $this->assertNull(Post::find($post->id));
    }

    public function test_unathorized_to_destroy()
    {
        Gate::policy(Post::class, PostPolicy::class);

        $post = factory(Post::class)->create(['user_id' => 1]);

        $_SERVER['restify.post.deletable'] = false;

        $this->delete('/restify-api/posts/' . $post->id, [
            'title' => 'Updated title',
        ])->assertStatus(403)
            ->assertJson([
                'errors' => ['This action is unauthorized.'],
            ]);

        $this->assertInstanceOf(Post::class, $post->refresh());
    }
}
