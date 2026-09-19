<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class BulkModelLoadingTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    #[Test]
    public function bulk_delete_loads_every_model_in_one_query(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

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
        Gate::policy(Post::class, PostPolicy::class);

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
