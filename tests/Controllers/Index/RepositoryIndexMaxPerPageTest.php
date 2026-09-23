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
    #[TestWith([500])]
    #[TestWith([1000])]
    #[TestWith([3000])]
    #[TestWith([9999])]
    public function it_honours_any_requested_per_page_when_no_cap_is_configured(int $requestedPerPage): void
    {
        config(['restify.pagination.max_per_page' => null]);

        CommentFactory::many(3);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => $requestedPerPage,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 3)
                ->where('meta.per_page', $requestedPerPage)
                ->etc()
        );
    }

    #[Test]
    public function it_clamps_a_requested_per_page_above_the_configured_cap(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        CommentFactory::many(55);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => 100,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 50)
                ->where('meta.per_page', 50)
                ->etc()
        );
    }

    #[Test]
    public function it_leaves_a_requested_per_page_under_the_cap_untouched(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        CommentFactory::many(3);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => 20,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 3)
                ->where('meta.per_page', 20)
                ->etc()
        );
    }

    #[Test]
    public function it_leaves_the_default_per_page_unchanged_when_absent_and_no_cap_is_configured(): void
    {
        config(['restify.pagination.max_per_page' => null]);

        CommentFactory::many(3);

        $this->getJson(CommentRepository::route())->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 3)
                ->where('meta.per_page', 15)
                ->etc()
        );
    }

    #[Test]
    public function it_clamps_the_repository_default_when_no_per_page_is_requested_and_the_cap_is_below_it(): void
    {
        config(['restify.pagination.max_per_page' => 10]);

        CommentFactory::many(20);

        $this->getJson(CommentRepository::route())->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 10)
                ->where('meta.per_page', 10)
                ->etc()
        );
    }

    #[Test]
    public function it_falls_back_to_the_default_per_page_for_a_non_numeric_request(): void
    {
        CommentFactory::many(3);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => 'not-a-number',
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 3)
                ->where('meta.per_page', 15)
                ->etc()
        );
    }

    #[Test]
    public function it_falls_back_to_the_default_per_page_for_a_negative_request(): void
    {
        CommentFactory::many(20);

        $this->getJson(CommentRepository::route(query: [
            'perPage' => -1,
        ]))->assertJson(
            fn (AssertableJson $json) => $json
                ->count('data', 15)
                ->where('meta.per_page', 15)
                ->etc()
        );
    }
}
