<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class BulkConsumerShapesTest extends IntegrationTestCase
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
            $_SERVER['restify.post.updateBulk.spy'],
            $_SERVER['restify.post.deleteBulk.spy'],
        );

        parent::tearDown();
    }

    #[Test]
    public function saved_bulk_can_pluck_a_column_the_update_payload_never_sent(): void
    {
        $posts = Post::factory(2)->sequence(
            ['user_id' => 1, 'title' => 'First', 'category' => 'alpha'],
            ['user_id' => 1, 'title' => 'Second', 'category' => 'beta'],
        )->create();

        $payload = $posts->map(fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => "Renamed {$post->getKey()}",
        ])->all();

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertSame(
            ['alpha', 'beta'],
            $this->hook('savedBulk')->pluck('category')->all(),
        );
    }

    #[Test]
    public function deleted_bulk_can_pluck_a_column_from_the_attribute_arrays(): void
    {
        Post::factory(2)->sequence(
            ['user_id' => 1, 'category' => 'alpha'],
            ['user_id' => 1, 'category' => 'beta'],
        )->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [1, 2])->assertOk();

        $this->assertSame(
            ['alpha', 'beta'],
            $this->hook('deletedBulk')->pluck('category')->all(),
        );
    }

    #[Test]
    public function an_update_bulk_override_receives_its_own_model_and_its_payload_row(): void
    {
        Post::factory(3)->create(['user_id' => 1]);

        $seen = [];

        $_SERVER['restify.post.updateBulk.spy'] = function ($repositoryId, int $row, Post $resource) use (&$seen): void {
            $seen[] = [$repositoryId, $row, $resource->getKey()];
        };

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => 3, 'title' => 'Third'],
            ['id' => 1, 'title' => 'First'],
            ['id' => 2, 'title' => 'Second'],
        ])->assertOk();

        $this->assertSame([[3, 0, 3], [1, 1, 1], [2, 2, 2]], $seen);
    }

    #[Test]
    public function an_update_bulk_override_that_throws_rolls_back_the_rows_before_it(): void
    {
        Post::factory(2)->create(['user_id' => 1, 'title' => 'Original']);

        $_SERVER['restify.post.updateBulk.spy'] = function ($repositoryId, int $row): void {
            if ($row === 1) {
                throw new HttpResponseException(
                    new JsonResponse(['message' => 'nope'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                );
            }
        };

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => 1, 'title' => 'Renamed one'],
            ['id' => 2, 'title' => 'Renamed two'],
        ])->assertUnprocessable();

        $this->assertDatabaseCount(Post::class, 2);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Renamed one']);
    }

    #[Test]
    public function a_delete_bulk_override_that_throws_rolls_back_the_rows_before_it(): void
    {
        Post::factory(2)->create(['user_id' => 1]);

        $_SERVER['restify.post.deleteBulk.spy'] = function ($repositoryId, int $row): void {
            if ($row === 1) {
                throw new HttpResponseException(
                    new JsonResponse(['message' => 'nope'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
                );
            }
        };

        $this->deleteJson(PostRepository::route('bulk/delete'), [1, 2])->assertUnprocessable();

        $this->assertDatabaseCount(Post::class, 2);
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
