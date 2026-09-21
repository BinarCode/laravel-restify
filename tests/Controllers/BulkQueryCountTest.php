<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class BulkQueryCountTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['restify.post.deleteBulk.callback']);

        parent::tearDown();
    }

    #[Test]
    public function bulk_delete_loads_every_model_in_one_query(): void
    {
        $posts = Post::factory(5)->create();

        $this->recordQueries();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts->modelKeys())->assertOk();

        $this->assertSelectCount(1, Post::class);
    }

    #[Test]
    public function bulk_update_loads_every_model_in_one_query(): void
    {
        $posts = Post::factory(5)->create(['user_id' => 1]);

        $payload = $posts->map(fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => 'Updated '.$post->getKey(),
        ])->all();

        $this->recordQueries();

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertSelectCount(1, Post::class);
    }

    #[Test]
    public function bulk_delete_authorizes_every_model_before_deleting_any(): void
    {
        $posts = Post::factory(3)->create();
        $denied = $posts->last();

        $_SERVER['restify.post.deleteBulk.callback'] = fn (
            $user,
            Post $post
        ): bool => $post->getKey() !== $denied->getKey();

        $this->recordQueries();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts->modelKeys())->assertForbidden();

        $this->assertSame([], $this->executedWrites());
    }
}
