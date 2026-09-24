<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Controllers\Index;

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
    #[TestWith([50, 100, 55, 50], 'requested value above the cap is clamped')]
    #[TestWith([50, 20, 3, 20], 'requested value under the cap is untouched')]
    #[TestWith([null, null, 3, 15], 'no cap, no request: default is untouched')]
    #[TestWith([10, null, 20, 10], 'cap below the default clamps the default')]
    #[TestWith([null, 'not-a-number', 3, 15], 'non-numeric request falls back to the default')]
    #[TestWith([null, -1, 20, 15], 'negative request falls back to the default')]
    public function it_resolves_the_index_per_page_against_the_configured_cap(
        ?int $maxPerPage,
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
