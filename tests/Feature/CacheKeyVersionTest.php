<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostWithCustomDateFormat;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\CarbonImmutable;
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

    #[Test]
    public function the_version_reads_a_model_date_format_the_parser_cannot_guess(): void
    {
        $post = PostWithCustomDateFormat::query()->create(['title' => 'Custom format']);

        $updatedAt = CarbonImmutable::now()->subYear();

        DB::table($post->getTable())->update([
            'updated_at' => $updatedAt->format($post->getDateFormat()),
        ]);

        $key = PostRepository::resolveWith(new PostWithCustomDateFormat)
            ->generateIndexCacheKey(app(RestifyRequest::class));

        $this->assertStringContainsString('v_'.$updatedAt->getTimestamp(), $key);
    }
}
