<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Database\Factories\CommentFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Comment\CommentRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RepositoryIndexMaxPerPageTest extends IntegrationTestCase
{
    use RefreshDatabase;

    #[Test]
    #[TestWith([null, 9999, 3, 9999], 'no cap: requested value is honoured')]
    #[TestWith([0, 9999, 3, 9999], 'cap turned off with 0: requested value is honoured')]
    #[TestWith([false, 9999, 3, 9999], 'cap turned off with false: requested value is honoured')]
    #[TestWith([500, 400, 3, 400], 'requested value under a large cap is untouched')]
    #[TestWith([50, 100, 55, 50], 'requested value above the cap is clamped')]
    #[TestWith([50, 20, 3, 20], 'requested value under the cap is untouched')]
    #[TestWith([null, null, 3, 15], 'no cap, no request: default is untouched')]
    #[TestWith([10, null, 20, 10], 'cap below the default clamps the default')]
    #[TestWith([null, 'not-a-number', 3, 15], 'non-numeric request falls back to the default')]
    #[TestWith([null, -1, 20, 15], 'negative request falls back to the default')]
    public function it_resolves_the_index_per_page_against_the_configured_cap(
        int|false|null $maxPerPage,
        int|string|null $requestedPerPage,
        int $seedCount,
        int $expectedPerPage,
    ): void {
        config(['restify.pagination.max_per_page' => $maxPerPage]);

        CommentFactory::many($seedCount);

        $query = $requestedPerPage === null ? [] : ['perPage' => $requestedPerPage];

        $this->getJson(CommentRepository::route(query: $query))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', min($seedCount, $expectedPerPage))
                ->where('meta.per_page', $expectedPerPage)
                ->etc()
        );
    }

    #[Test]
    public function it_caps_page_size_and_honours_page_number_on_the_second_page(): void
    {
        config(['restify.pagination.max_per_page' => 10]);

        CommentFactory::many(25);

        $this->getJson(CommentRepository::route(query: [
            'page' => ['size' => 100, 'number' => 2],
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 10)
                ->where('meta.current_page', 2)
                ->where('meta.per_page', 10)
                ->etc()
        );
    }

    #[Test]
    public function an_unset_cap_serves_a_large_per_page_in_full(): void
    {
        // A published config holds `env('RESTIFY_MAX_PER_PAGE')`, which is null when the variable is unset.
        $this->assertNull(config('restify.pagination.max_per_page'));

        CommentFactory::many(120);

        $this->getJson(CommentRepository::route(query: ['perPage' => 500]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 120)
                ->where('meta.per_page', 500)
                ->where('meta.last_page', 1)
                ->etc()
        );
    }

    #[Test]
    #[TestWith([null, 5000], 'no cap')]
    #[TestWith([500, 500], 'cap set in config')]
    #[TestWith([0, 5000], 'cap turned off')]
    public function it_resolves_page_size_against_the_cap(?int $maxPerPage, int $expectedPerPage): void
    {
        config(['restify.pagination.max_per_page' => $maxPerPage]);

        CommentFactory::many(3);

        $this->getJson(CommentRepository::route(query: [
            'page' => ['size' => 5000, 'number' => 1],
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 3)
                ->where('meta.per_page', $expectedPerPage)
                ->etc()
        );
    }

    #[Test]
    public function there_is_no_cap_when_the_published_config_predates_the_pagination_key(): void
    {
        config(['restify.pagination' => null]);

        CommentFactory::many(3);

        $this->getJson(CommentRepository::route(query: ['perPage' => 9999]))->assertJson(
            fn (AssertableJson $json) => $json->where('meta.per_page', 9999)->etc()
        );
    }

    #[Test]
    public function a_configured_cap_still_clamps_a_larger_repository_default(): void
    {
        Restify::repositories([CommentWithLargePageRepository::class]);

        config(['restify.pagination.max_per_page' => 100]);

        CommentFactory::many(3);

        $this->getJson(CommentWithLargePageRepository::route())->assertJson(
            fn (AssertableJson $json) => $json->where('meta.per_page', 100)->etc()
        );
    }

    #[Test]
    public function it_falls_back_to_the_default_when_per_page_is_sent_as_an_array(): void
    {
        CommentFactory::many(20);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => [5],
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 15)
                ->where('meta.per_page', 15)
                ->etc()
        );
    }
}

class CommentWithLargePageRepository extends CommentRepository
{
    public static string $uriKey = 'comments-with-large-page';

    public static int $defaultPerPage = 250;
}
