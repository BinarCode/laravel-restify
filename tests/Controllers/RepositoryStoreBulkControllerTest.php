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

    public function test_basic_validation_works(): void
    {
        $this->postJson('posts/bulk', [
            [
                'title' => null,
            ],
        ])
            ->assertStatus(422);
    }

    public function test_unauthorized_store_bulk(): void
    {
        $_SERVER['restify.post.storeBulk'] = false;

        Gate::policy(Post::class, PostPolicy::class);

        $this->postJson('posts/bulk', [
            [
                'title' => 'Title',
                'description' => 'Title',
            ],
        ])->assertStatus(403);
    }

    public function test_user_can_bulk_create_posts(): void
    {
        $user = $this->mockUsers()->first();
        $this->postJson('posts/bulk', [
            [
                'user_id' => $user->getKey(),
                'title' => 'First post.',
            ],
            [
                'user_id' => $user->getKey(),
                'title' => 'Second post.',
            ],
        ])->assertSuccessful();

        $this->assertDatabaseCount('posts', 2);
    }
}
