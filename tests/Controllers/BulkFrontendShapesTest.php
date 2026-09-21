<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class BulkFrontendShapesTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    protected function tearDown(): void
    {
        unset(
            $_SERVER['restify.post.updateBulk.callback'],
            $_SERVER['restify.post.deleteBulk.callback'],
        );

        parent::tearDown();
    }

    #[Test]
    public function delete_bulk_accepts_the_string_ids_the_repository_serialized(): void
    {
        $posts = Post::factory(3)->create();

        $this->recordQueries();

        $this->deleteJson(PostRepository::route('bulk/delete'), $this->serializedKeys($posts))
            ->assertOk();

        $this->assertSelectCount(1, Post::class);
        $this->assertDatabaseCount(Post::class, 0);
    }

    #[Test]
    public function update_bulk_accepts_string_ids_and_ignores_keys_that_are_not_fields(): void
    {
        $posts = Post::factory(3)->create(['user_id' => 1, 'title' => 'Original']);

        $payload = $posts->map(fn (Post $post): array => [
            'id' => (string) $post->getKey(),
            'title' => "Renamed {$post->getKey()}",
            'dirty' => true,
            'meta' => ['authorizedToDelete' => true],
            'user' => ['id' => '1', 'name' => 'nested relation object'],
        ])->all();

        $this->recordQueries();

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertSelectCount(1, Post::class);

        foreach ($posts as $post) {
            $this->assertDatabaseHas(Post::class, [
                'id' => $post->getKey(),
                'title' => "Renamed {$post->getKey()}",
            ]);
        }
    }

    #[Test]
    public function update_bulk_applies_each_row_to_its_own_model_when_the_ids_arrive_shuffled(): void
    {
        Post::factory(3)->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => '3', 'title' => 'Third'],
            ['id' => '1', 'title' => 'First'],
            ['id' => '2', 'title' => 'Second'],
        ])->assertOk();

        $this->assertDatabaseHas(Post::class, ['id' => 1, 'title' => 'First']);
        $this->assertDatabaseHas(Post::class, ['id' => 2, 'title' => 'Second']);
        $this->assertDatabaseHas(Post::class, ['id' => 3, 'title' => 'Third']);
    }

    #[Test]
    public function delete_bulk_writes_nothing_when_the_grid_holds_a_row_that_is_already_gone(): void
    {
        $posts = Post::factory(3)->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            ...$this->serializedKeys($posts),
            '9999',
        ])->assertNotFound();

        $this->assertDatabaseCount(Post::class, 3);
    }

    #[Test]
    public function update_bulk_writes_nothing_when_the_grid_holds_a_row_that_is_already_gone(): void
    {
        Post::factory(2)->create(['user_id' => 1, 'title' => 'Original']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => '1', 'title' => 'Renamed'],
            ['id' => '9999', 'title' => 'Ghost'],
        ])->assertNotFound();

        $this->assertDatabaseMissing(Post::class, ['title' => 'Renamed']);
    }

    #[Test]
    public function delete_bulk_writes_nothing_when_the_policy_denies_a_row_that_is_not_the_first(): void
    {
        $posts = Post::factory(3)->create();

        $_SERVER['restify.post.deleteBulk.callback'] = fn ($user, Post $post): bool => $post->getKey() !== 3;

        $this->deleteJson(PostRepository::route('bulk/delete'), $this->serializedKeys($posts))
            ->assertForbidden();

        $this->assertDatabaseCount(Post::class, 3);
    }

    #[Test]
    public function update_bulk_writes_nothing_when_the_policy_denies_a_row_that_is_not_the_first(): void
    {
        Post::factory(3)->create(['user_id' => 1, 'title' => 'Original']);

        $_SERVER['restify.post.updateBulk.callback'] = fn ($user, Post $post): bool => $post->getKey() !== 3;

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => '1', 'title' => 'Renamed one'],
            ['id' => '2', 'title' => 'Renamed two'],
            ['id' => '3', 'title' => 'Renamed three'],
        ])->assertForbidden();

        $this->assertDatabaseMissing(Post::class, ['title' => 'Renamed one']);
        $this->assertDatabaseCount(Post::class, 3);
    }

    #[Test]
    public function store_bulk_creates_every_row_and_rolls_the_batch_back_when_one_fails_validation(): void
    {
        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'Created one', 'user_id' => 1],
            ['title' => 'Created two', 'user_id' => 1],
        ])->assertOk();

        $this->assertDatabaseCount(Post::class, 2);

        $this->postJson(PostRepository::route('bulk'), [
            ['title' => 'Created three', 'user_id' => 1],
            ['user_id' => 1],
        ])->assertUnprocessable();

        $this->assertDatabaseCount(Post::class, 2);
        $this->assertDatabaseMissing(Post::class, ['title' => 'Created three']);
    }

    #[Test]
    public function a_batch_larger_than_the_lookup_chunk_still_resolves_every_row(): void
    {
        $keys = $this->seedPosts(601);

        $this->recordQueries();

        $this->deleteJson(PostRepository::route('bulk/delete'), $keys)->assertOk();

        $this->assertSelectCount(2, Post::class);
        $this->assertDatabaseCount(Post::class, 0);
    }

    /**
     * @param  Collection<int, Post>  $posts
     * @return list<string>
     */
    private function serializedKeys(Collection $posts): array
    {
        return $posts->map(static fn (Post $post): string => (string) $post->getKey())->values()->all();
    }

    /**
     * @return list<string>
     */
    private function seedPosts(int $count): array
    {
        return $this->serializedKeys(Post::factory($count)->create(['user_id' => 1]));
    }
}
