<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;

class CacheKeyVersionTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->enableRepositoryCache();
    }

    protected function tearDown(): void
    {
        $this->clearRepositoryCache();

        parent::tearDown();
    }

    #[Test]
    public function an_index_still_works_when_every_timestamp_is_null(): void
    {
        $post = Post::factory()->create(['title' => 'No timestamp']);

        DB::table($post->getTable())->update(['updated_at' => null]);

        $this->getJson(PostRepository::route())
            ->assertOk()
            ->assertJsonFragment(['title' => 'No timestamp']);
    }
}
