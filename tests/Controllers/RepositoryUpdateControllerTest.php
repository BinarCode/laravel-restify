<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Mergeable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Apple;
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

        $this->withoutExceptionHandling()->patch('/restify-api/posts/'.$post->id, [
            'title' => 'Updated title',
        ])
            ->assertStatus(200);

        $updatedPost = Post::find($post->id);

        $this->assertEquals($updatedPost->title, 'Updated title');
    }

    public function test_put_works()
    {
        $post = factory(Post::class)->create(['user_id' => 1]);

        $this->withoutExceptionHandling()->put('/restify-api/posts/'.$post->id, [
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

        $this->patch('/restify-api/posts/'.$post->id, [
            'title' => 'Updated title',
        ])->assertStatus(403)
            ->assertJson([
                'errors' => ['This action is unauthorized.'],
            ]);
    }

    public function test_do_not_update_fields_without_permission()
    {
        Restify::repositories([AppleUnauthorizedField::class]);

        $post = factory(Apple::class)->create(['user_id' => 1, 'title' => 'Title']);

        $_SERVER['restify.apple.updateable'] = false;

        $response = $this->putJson('/restify-api/apple-unauthorized-put/'.$post->id, [
            'title' => 'Updated title',
            'user_id' => 2,
        ])->assertStatus(200);

        $this->assertEquals('Title', $response->json('data.attributes.title'));
        $this->assertEquals(2, $response->json('data.attributes.user_id'));
    }

    public function test_update_fillable_fields_for_mergeable_repository()
    {
        Restify::repositories([
            AppleUpdateMergeable::class,
        ]);

        $apple = factory(Apple::class)->create(['user_id' => 1, 'title' => 'Title', 'color' => 'red']);

        $response = $this->putJson('/restify-api/apple-update-extra/'.$apple->id, [
            'title' => 'Updated title',
            'color' => 'blue',
            'user_id' => 2,
        ])
            ->dump()
            ->assertStatus(200);

        $this->assertEquals('Updated title', $response->json('data.attributes.title')); // via extra
        $this->assertEquals('blue', $response->json('data.attributes.color')); // via extra
        $this->assertEquals(2, $response->json('data.attributes.user_id')); // via field
    }

    public function test_will_not_update_readonly_fields()
    {
        $user = $this->mockUsers()->first();

        $post = factory(Post::class)->create(['image' => null]);

        $r = $this->putJson('/restify-api/posts-unauthorized-fields/'.$post->id, [
            'user_id' => $user->id,
            'image' => 'avatar.png',
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->dump()
            ->assertStatus(200);

        $this->assertNull($r->json('data.attributes.image'));
    }
}

class AppleUnauthorizedField extends Repository
{
    public static $uriKey = 'apple-unauthorized-put';

    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title')->canUpdate(fn ($value) => $_SERVER['restify.apple.updateable']),

            Field::make('user_id')->canUpdate(fn ($value) => true),
        ];
    }
}

class AppleUpdateMergeable extends Repository implements Mergeable
{
    public static $uriKey = 'apple-update-extra';

    public static $model = Apple::class;

    public function fields(RestifyRequest $request)
    {
        return [
            Field::make('title')->canUpdate(fn ($value) => true),

            Field::make('user_id')->canUpdate(fn ($value) => true),
        ];
    }
}
