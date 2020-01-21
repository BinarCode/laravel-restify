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
        $this->withExceptionHandling()->post('/restify-api/posts', [
            'title' => 'Title',
        ])
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        'description' => [
                            'Description field is required',
                        ],
                    ]
                ],
            ]);
    }

    public function test_unauthorized_store()
    {
        $_SERVER['restify.user.creatable'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->withExceptionHandling()->post('/restify-api/posts', [
            'title' => 'Title',
            'description' => 'Title',
        ])->assertStatus(403)
            ->assertJson(['errors' => ['Unauthorized to create.']]);
    }

    public function test_success_storing()
    {
        $user = $this->mockUsers()->first();
        $r = json_decode($this->withExceptionHandling()->post('/restify-api/posts', [
            'user_id' => $user->id,
            'title' => 'Some post title',
            'description' => 'A very short description',
        ])
            ->assertStatus(201)
            ->assertHeader('Location', '/restify-api/posts/1')
            ->getContent());

        $this->assertEquals($r->data->attributes->title, 'Some post title');
        $this->assertEquals($r->data->attributes->description, 'A very short description');
        $this->assertEquals($r->data->attributes->user_id, $user->id);
        $this->assertEquals($r->data->id, 1);
        $this->assertEquals($r->data->type, 'posts');
    }
}
