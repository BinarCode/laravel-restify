<?php

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Support\Facades\Gate;

class RepositoryStoreBulkControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_basic_validation_works()
    {
        $this->postJson('/restify-api/posts/bulk', [
            [
                'title' => null,
            ],
        ])
            ->assertStatus(400)
            ->assertJson([
                'errors' => [
                    [
                        '0.title' => [
                            'This field is required',
                        ],
                    ],
                ],
            ]);
    }

    public function test_unauthorized_store_bulk()
    {
        $_SERVER['restify.post.storeBulk'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->postJson('/restify-api/posts/bulk', [
            [
                'title' => 'Title',
                'description' => 'Title',
            ],
        ])->assertStatus(403)
            ->assertJson(['errors' => ['Unauthorized to store bulk.']]);
    }

    public function test_user_can_bulk_create_posts()
    {
        $user = $this->mockUsers()->first();

        $this->postJson('/restify-api/posts/bulk', [
            [
                'user_id' => $user->id,
                'title' => 'First post.',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Second post.',
            ],
        ])
            ->assertStatus(201);

        $this->assertDatabaseCount('posts', 2);
    }
}
