<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class BulkModelLoadingTest extends IntegrationTestCase
{
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

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts->modelKeys())->assertOk();

        $this->assertCount(1, $this->selectsAgainst('posts'));
    }

    #[Test]
    public function bulk_update_loads_every_model_in_one_query(): void
    {
        $posts = Post::factory(5)->create(['user_id' => 1]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $payload = $posts->map(fn (Post $post): array => [
            'id' => $post->getKey(),
            'title' => 'Updated '.$post->getKey(),
        ])->all();

        $this->postJson(PostRepository::route('bulk/update'), $payload)->assertOk();

        $this->assertCount(1, $this->selectsAgainst('posts'));
    }

    #[Test]
    public function bulk_delete_with_an_unknown_key_changes_nothing(): void
    {
        $posts = Post::factory(2)->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            ...$posts->modelKeys(),
            9999,
        ])->assertNotFound();

        $posts->each(fn (Post $post) => $this->assertModelExists($post));
    }

    #[Test]
    public function bulk_update_with_an_unknown_key_changes_nothing(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original title']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post->getKey(), 'title' => 'Updated title'],
            ['id' => 9999, 'title' => 'Updated missing title'],
        ])->assertNotFound();

        $this->assertSame('Original title', $post->fresh()->title);
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

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->deleteJson(PostRepository::route('bulk/delete'), $posts->modelKeys())->assertForbidden();

        $this->assertSame([], $this->writes());
    }

    #[Test]
    public function bulk_delete_handles_a_repeated_key_once(): void
    {
        $post = Post::factory()->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            $post->getKey(),
            $post->getKey(),
        ])->assertOk();

        $this->assertModelMissing($post);
        $this->assertSame(1, DB::table('action_logs')->count());
    }

    #[Test]
    public function bulk_delete_accepts_a_key_the_database_matches(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson(PostRepository::route('bulk/delete'), [
            '0'.$post->getKey(),
        ])->assertOk();

        $this->assertModelMissing($post);
    }

    #[Test]
    public function bulk_delete_ignores_the_query_string(): void
    {
        $post = Post::factory()->create();

        $this->deleteJson(
            PostRepository::route('bulk/delete', ['foo' => 'bar']),
            [$post->getKey()],
        )->assertOk();

        $this->assertModelMissing($post);
    }

    #[Test]
    public function bulk_update_with_an_item_missing_its_id_changes_nothing(): void
    {
        $post = Post::factory()->create(['user_id' => 1, 'title' => 'Original title']);

        $this->postJson(PostRepository::route('bulk/update'), [
            ['id' => $post->getKey(), 'title' => 'Updated title'],
            ['title' => 'No id here'],
        ])->assertNotFound();

        $this->assertSame('Original title', $post->fresh()->title);
    }

    /**
     * @return list<string>
     */
    private function writes(): array
    {
        return array_values(array_filter(
            array_column(DB::getQueryLog(), 'query'),
            fn (string $query): bool => str_starts_with($query, 'delete ')
                || str_starts_with($query, 'insert '),
        ));
    }

    /**
     * @return list<string>
     */
    private function selectsAgainst(string $table): array
    {
        $executed = array_column(DB::getQueryLog(), 'query');

        return array_values(array_filter(
            $executed,
            fn (string $query): bool => str_starts_with($query, 'select ')
                && str_contains($query, 'from "'.$table.'"'),
        ));
    }
}
