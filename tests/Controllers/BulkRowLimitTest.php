<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class BulkRowLimitTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    private const DEFAULT_LIMIT = 1000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function bulk_store_rejects_more_rows_than_the_default_limit_and_stores_nothing(): void
    {
        $this->postJson(PostRepository::route('bulk'), $this->storeRows(self::DEFAULT_LIMIT + 1))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'bulk' => 'A bulk request may contain at most 1000 rows, 1001 were sent.',
            ]);

        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function bulk_update_rejects_more_rows_than_the_default_limit_and_updates_nothing(): void
    {
        $posts = $this->seedPosts(self::DEFAULT_LIMIT + 1);

        $this->recordQueries();

        $this->postJson(PostRepository::route('bulk/update'), $this->updateRows($posts))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertSelectCount(0, Post::class);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Updated']);
    }

    #[Test]
    public function bulk_delete_rejects_more_keys_than_the_default_limit_and_deletes_nothing(): void
    {
        $posts = $this->seedPosts(self::DEFAULT_LIMIT + 1);

        $this->recordQueries();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertSelectCount(0, Post::class);
        $this->assertDatabaseCount(Post::class, self::DEFAULT_LIMIT + 1);
    }

    #[Test]
    public function a_bulk_request_with_exactly_the_default_limit_is_accepted(): void
    {
        $posts = $this->seedPosts(self::DEFAULT_LIMIT);

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts)->assertOk();

        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    #[TestWith(['2000'], 'limit raised, as env() provides it')]
    #[TestWith([0], 'limit turned off')]
    public function a_raised_or_disabled_limit_accepts_more_rows_than_the_default(int|string $maxRows): void
    {
        config(['restify.bulk.max_rows' => $maxRows]);

        $posts = $this->seedPosts(self::DEFAULT_LIMIT + 1);

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts)->assertOk();

        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function the_default_limit_applies_when_the_published_config_predates_the_bulk_key(): void
    {
        config(['restify' => Arr::except(config('restify'), ['bulk'])]);

        $posts = $this->seedPosts(self::DEFAULT_LIMIT + 1);

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertDatabaseCount(Post::class, self::DEFAULT_LIMIT + 1);
    }

    #[Test]
    public function bulk_store_honours_a_configured_limit_at_its_boundary(): void
    {
        config(['restify.bulk.max_rows' => 3]);

        $this->postJson(PostRepository::route('bulk'), $this->storeRows(4))
            ->assertUnprocessable()
            ->assertExactJson([
                'message' => 'A bulk request may contain at most 3 rows, 4 were sent.',
                'errors' => [
                    'bulk' => ['A bulk request may contain at most 3 rows, 4 were sent.'],
                ],
            ]);

        $this->assertDatabaseCount(Post::class, 0);

        $this->postJson(PostRepository::route('bulk'), $this->storeRows(3))->assertOk();

        $this->assertDatabaseCount(Post::class, 3);
    }

    #[Test]
    public function bulk_update_honours_a_configured_limit_at_its_boundary(): void
    {
        config(['restify.bulk.max_rows' => 3]);

        $posts = $this->seedPosts(4);

        $this->postJson(PostRepository::route('bulk/update'), $this->updateRows($posts))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertDatabaseMissing(Post::class, ['title' => 'Updated']);

        $this->postJson(PostRepository::route('bulk/update'), $this->updateRows(array_slice($posts, 0, 3)))
            ->assertOk();

        $this->assertSame(3, Post::query()->where('title', 'Updated')->count());
    }

    #[Test]
    public function bulk_delete_honours_a_configured_limit_at_its_boundary(): void
    {
        config(['restify.bulk.max_rows' => 3]);

        $posts = $this->seedPosts(4);

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertDatabaseCount(Post::class, 4);

        $this->deleteJson(PostRepository::route('bulk/delete'), array_slice($posts, 0, 3))->assertOk();

        $this->assertDatabaseCount(Post::class, 1);
    }

    #[Test]
    public function an_oversized_request_is_rejected_before_its_rows_are_validated(): void
    {
        config(['restify.bulk.max_rows' => 2]);

        $response = $this->postJson(PostRepository::route('bulk'), [
            ['title' => null],
            ['title' => null],
            ['title' => null],
        ])->assertUnprocessable();

        $this->assertSame(['bulk'], array_keys($response->json('errors')));
    }

    #[Test]
    public function a_form_encoded_bulk_request_is_limited_too(): void
    {
        config(['restify.bulk.max_rows' => 2]);

        $this->post(PostRepository::route('bulk'), $this->storeRows(3), ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bulk');

        $this->assertDatabaseCount(Post::class, 0);
    }

    /**
     * @return list<array{title: string, user_id: int}>
     */
    private function storeRows(int $count): array
    {
        return array_map(
            static fn (int $row): array => ['title' => "Post {$row}", 'user_id' => 1],
            range(1, $count),
        );
    }

    /**
     * @param  list<int>  $keys
     * @return list<array{id: int, title: string}>
     */
    private function updateRows(array $keys): array
    {
        return array_map(static fn (int $key): array => ['id' => $key, 'title' => 'Updated'], $keys);
    }

    /**
     * @return list<int>
     */
    private function seedPosts(int $count): array
    {
        foreach (array_chunk(range(1, $count), 200) as $chunk) {
            Post::query()->insert(array_map(
                static fn (int $row): array => ['id' => $row, 'user_id' => 1, 'title' => "Post {$row}"],
                $chunk,
            ));
        }

        return range(1, $count);
    }
}
