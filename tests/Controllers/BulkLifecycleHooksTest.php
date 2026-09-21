<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class BulkLifecycleHooksTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['restify.post.savedBulk'],
            $_SERVER['restify.post.updatedBulk'],
            $_SERVER['restify.post.deletedBulk'],
        );

        parent::tearDown();
    }

    #[Test]
    public function update_bulk_hands_the_hooks_the_updated_models(): void
    {
        $posts = Post::factory(2)->create(['user_id' => 1]);

        $payload = $posts->map(fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => "Updated {$post->getKey()}",
        ])->all();

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => 1, 'title' => 'Updated 1']);
        $this->assertDatabaseHas(Post::class, ['id' => 2, 'title' => 'Updated 2']);

        foreach (['savedBulk', 'updatedBulk'] as $name) {
            $received = $this->hook($name);

            $this->assertCount(2, $received, "{$name}() received the wrong number of entries");
            $this->assertContainsOnlyInstancesOf(Post::class, $received);
            $this->assertSame(
                ['Updated 1', 'Updated 2'],
                $received->map(fn (Post $post): string => $post->title)->all(),
            );
        }
    }

    #[Test]
    public function delete_bulk_hands_the_hooks_the_deleted_attributes(): void
    {
        $posts = Post::factory(2)->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts->modelKeys())->assertOk();

        $this->assertDatabaseCount(Post::class, 0);

        $deleted = $this->hook('deletedBulk');

        $this->assertCount(2, $deleted);
        $this->assertIsArray($deleted->first());
        $this->assertSame($posts->first()->getKey(), $deleted->first()['id']);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function hook(string $name): Collection
    {
        $this->assertArrayHasKey("restify.post.{$name}", $_SERVER, "{$name}() was never called");

        return $_SERVER["restify.post.{$name}"];
    }
}
