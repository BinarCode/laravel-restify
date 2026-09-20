<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class CacheKeyQueryTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'restify.repositories.cache.enabled' => true,
            'restify.repositories.cache.enable_in_tests' => true,
        ]);

        Post::factory(3)->create();

        $this->recordQueries();
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
}
