<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class BulkQueryStringTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    #[TestWith(['postJson'], 'json body')]
    #[TestWith(['post'], 'form encoded body')]
    public function bulk_store_ignores_the_query_string(string $method): void
    {
        $this->{$method}(PostRepository::route('bulk', ['foo' => 'bar']), [
            ['title' => 'Created one', 'user_id' => 1],
        ])->assertOk();

        $this->assertDatabaseCount(Post::class, 1);
        $this->assertDatabaseHas(Post::class, ['title' => 'Created one']);
    }

    #[Test]
    #[TestWith(['postJson'], 'json body')]
    #[TestWith(['post'], 'form encoded body')]
    public function bulk_update_ignores_the_query_string(string $method): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original']);

        $this->{$method}(PostRepository::route('bulk/update', ['foo' => 'bar']), [
            ['id' => $post->getKey(), 'title' => 'Updated'],
        ])->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Updated']);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Original']);
    }

    #[Test]
    public function bulk_store_keeps_an_uploaded_file_in_the_payload(): void
    {
        $this->post(PostRepository::route('bulk', ['foo' => 'bar']), [
            ['title' => 'Created one', 'user_id' => 1, 'image' => UploadedFile::fake()->image('cover.jpg')],
        ])->assertOk();

        $this->assertDatabaseCount(Post::class, 1);
        $this->assertDatabaseHas(Post::class, ['title' => 'Created one']);
    }
}
