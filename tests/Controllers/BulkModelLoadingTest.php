<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Models\ActionLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class BulkModelLoadingTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function bulk_delete_with_an_unknown_key_changes_nothing(): void
    {
        $posts = Post::factory(2)->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            ...$posts->modelKeys(),
            9999,
        ])->assertNotFound();

        $this->assertDatabaseCount(Post::class, 2);
    }

    #[Test]
    public function bulk_update_with_an_unknown_key_changes_nothing(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original title']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post->getKey(), 'title' => 'Updated title'],
            ['id' => 9999, 'title' => 'Updated missing title'],
        ])->assertNotFound();

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Original title']);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Updated title']);
    }

    #[Test]
    public function bulk_delete_handles_a_repeated_key_once(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            $post->getKey(),
            $post->getKey(),
        ])->assertOk();

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
        $this->assertDatabaseCount(ActionLog::class, 1);
    }

    #[Test]
    public function bulk_delete_accepts_a_key_the_database_matches(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            '0'.$post->getKey(),
        ])->assertOk();

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function bulk_delete_ignores_the_query_string(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson(
            PostRepository::route('bulk/delete', ['foo' => 'bar']),
            [$post->getKey()],
        )->assertOk();

        $this->assertDatabaseMissing(Post::class, ['id' => $post->getKey()]);
    }

    #[Test]
    public function bulk_update_with_an_item_missing_its_id_changes_nothing(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original title']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post->getKey(), 'title' => 'Updated title'],
            ['title' => 'No id here'],
        ])->assertNotFound();

        $this->assertDatabaseHas(Post::class, ['id' => $post->getKey(), 'title' => 'Original title']);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Updated title']);
    }
}
