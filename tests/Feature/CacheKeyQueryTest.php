<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class CacheKeyQueryTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->enableRepositoryCache();

        Post::factory(3)->create(['title' => 'Original title']);

        $this->recordQueries();
    }

    protected function tearDown(): void
    {
        $this->clearRepositoryCache();

        parent::tearDown();
    }

    #[Test]
    public function the_cache_key_reads_the_newest_timestamp_with_an_aggregate(): void
    {
        $this->getJson(PostRepository::route())->assertOk();

        $this->assertNotEmpty(
            array_filter(
                $this->executedQueries(),
                fn (string $query): bool => str_contains($query, 'max("updated_at")'),
            ),
            'the cache key should read the newest timestamp with an aggregate. executed: '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );

        $this->assertSame(
            [],
            array_values(array_filter(
                $this->executedQueries(),
                fn (string $query): bool => str_contains($query, 'order by "updated_at" desc'),
            )),
            'the cache key should not hydrate the newest row',
        );
    }

    #[Test]
    public function a_repeated_request_is_served_from_the_cache(): void
    {
        $this->getJson(PostRepository::route())->assertOk();

        $onMiss = count($this->selectsAgainst(Post::class));

        $this->recordQueries();

        $this->getJson(PostRepository::route())->assertOk();

        $onHit = count($this->selectsAgainst(Post::class));

        $this->assertGreaterThan(
            $onHit,
            $onMiss,
            'the second identical request should read fewer times than the first. '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );
    }

    #[Test]
    public function a_row_touched_without_model_events_busts_the_cache(): void
    {
        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonFragment(['title' => 'Original title']);

        $this->recordQueries();

        $this->getJson(PostRepository::route())->assertOk();

        $this->assertCount(
            1,
            $this->selectsAgainst(Post::class),
            'the second request should be a cache hit, leaving only the key lookup',
        );

        DB::table((new Post)->getTable())->update([
            'title' => 'Updated title',
            'updated_at' => now()->addMinute(),
        ]);

        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonFragment(['title' => 'Updated title']);
    }
}
