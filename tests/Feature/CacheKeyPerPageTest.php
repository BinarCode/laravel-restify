<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class CacheKeyPerPageTest extends IntegrationTestCase
{
    private int $originalDefaultPerPage;

    protected function setUp(): void
    {
        parent::setUp();

        // Array cache avoids the database cache table CI does not migrate.
        config(['cache.default' => 'array']);

        $this->originalDefaultPerPage = PostRepository::$defaultPerPage;
    }

    protected function tearDown(): void
    {
        // $defaultPerPage is inherited from Repository, so every repository
        // that does not declare its own copy shares this static with PostRepository.
        PostRepository::$defaultPerPage = $this->originalDefaultPerPage;

        parent::tearDown();
    }

    #[Test]
    public function the_index_cache_key_matches_an_explicit_per_page_equal_to_the_repository_default_and_differs_from_fifteen(): void
    {
        PostRepository::$defaultPerPage = 50;

        $repository = PostRepository::resolveWith(new Post);

        $withoutPerPage = $repository->generateIndexCacheKey(
            RestifyRequest::create('/', 'GET')
        );

        $withMatchingDefaultPerPage = $repository->generateIndexCacheKey(
            RestifyRequest::create('/', 'GET', ['perPage' => 50])
        );

        $withFifteenPerPage = $repository->generateIndexCacheKey(
            RestifyRequest::create('/', 'GET', ['perPage' => 15])
        );

        $this->assertSame($withoutPerPage, $withMatchingDefaultPerPage);
        $this->assertNotSame($withoutPerPage, $withFifteenPerPage);
    }

    #[Test]
    public function the_index_cache_key_for_a_per_page_capped_down_to_a_value_matches_the_key_for_that_value(): void
    {
        config(['restify.pagination.max_per_page' => 100]);

        $repository = PostRepository::resolveWith(new Post);

        $withPerPageCappedTo100 = $repository->generateIndexCacheKey(
            RestifyRequest::create('/', 'GET', ['perPage' => 500])
        );

        $withPerPageOf100 = $repository->generateIndexCacheKey(
            RestifyRequest::create('/', 'GET', ['perPage' => 100])
        );

        $this->assertSame($withPerPageOf100, $withPerPageCappedTo100);
    }
}
