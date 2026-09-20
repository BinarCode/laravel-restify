<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class BulkQueryStringTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function bulk_store_ignores_the_query_string(): void
    {
        $this->postJson(PostRepository::route('bulk', ['foo' => 'bar']), [
            ['title' => 'Created one', 'user_id' => 1],
        ])->assertOk();

        $this->assertDatabaseCount(Post::class, 1);
        $this->assertDatabaseHas(Post::class, ['title' => 'Created one']);
    }

    #[Test]
    public function bulk_update_ignores_the_query_string(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update', ['foo' => 'bar']), [
            ['id' => $post->getKey(), 'title' => 'Updated'],
        ])->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Updated']);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Original']);
    }
}
