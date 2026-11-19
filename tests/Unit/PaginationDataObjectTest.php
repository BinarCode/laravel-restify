<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class PaginationDataObjectTest extends IntegrationTestCase
{
    #[Test]
    #[TestWith([20, 20], 'requested value is used over the default')]
    #[TestWith([null, 15], 'default used when perPage is absent')]
    #[TestWith(['abc', 15], 'default used when perPage is non-numeric')]
    #[TestWith([0, 15], 'default used when perPage is zero')]
    #[TestWith([-5, 15], 'default used when perPage is negative')]
    #[TestWith(['20', 20], 'numeric string perPage is honoured')]
    #[TestWith([9999, 9999], 'a very large perPage is honoured')]
    public function it_resolves_per_page_without_a_cap(int|string|null $perPage, int $expected): void
    {
        config(['restify.pagination.max_per_page' => 0]);

        $pagination = new PaginationDataObject(perPage: $perPage, page: null);

        $this->assertSame($expected, $pagination->resolvePerPage(15));
    }

    #[Test]
    #[TestWith([20, 15, 20], 'requested value under the default cap is untouched')]
    #[TestWith([100, 15, 100], 'requested value equal to the default cap is untouched')]
    #[TestWith([101, 15, 100], 'requested value above the default cap is clamped down to it')]
    #[TestWith(['9999', 15, 100], 'numeric string above the default cap is clamped down to it')]
    #[TestWith([null, 15, 15], 'repository default is used when perPage is absent')]
    #[TestWith([null, 250, 250], 'repository default above the default cap is kept')]
    #[TestWith([250, 250, 250], 'requested value up to a larger repository default is honoured')]
    #[TestWith([9999, 250, 250], 'requested value above a larger repository default is clamped down to that default')]
    public function it_clamps_per_page_to_the_package_default_when_no_cap_is_configured(
        int|string|null $perPage,
        int $repositoryDefault,
        int $expected,
    ): void {
        config(['restify.pagination.max_per_page' => null]);

        $pagination = new PaginationDataObject(perPage: $perPage, page: null);

        $this->assertSame($expected, $pagination->resolvePerPage($repositoryDefault));
    }

    #[Test]
    public function it_applies_the_package_default_when_the_published_config_has_no_pagination_key(): void
    {
        config(['restify.pagination' => null]);

        $pagination = new PaginationDataObject(perPage: 9999, page: null);

        $this->assertSame(100, $pagination->resolvePerPage(15));
    }

    #[Test]
    public function a_configured_cap_still_clamps_a_repository_default_above_it(): void
    {
        config(['restify.pagination.max_per_page' => 50]);

        $pagination = new PaginationDataObject(perPage: null, page: null);

        $this->assertSame(50, $pagination->resolvePerPage(250));
    }

    #[Test]
    #[TestWith([100, 50, 50], 'requested value above the cap is clamped down to it')]
    #[TestWith([20, 50, 20], 'requested value under the cap is untouched')]
    #[TestWith([50, 50, 50], 'requested value equal to the cap is untouched')]
    public function it_clamps_per_page_to_the_configured_max(int $perPage, int $maxPerPage, int $expected): void
    {
        config(['restify.pagination.max_per_page' => $maxPerPage]);

        $pagination = new PaginationDataObject(perPage: $perPage, page: null);

        $this->assertSame($expected, $pagination->resolvePerPage(15));
    }

    #[Test]
    #[TestWith(['50', 100, 50], "string cap '50', as env() provides it, clamps a value above it")]
    #[TestWith(['500', 400, 400], "string cap '500' raises the cap above the package default")]
    #[TestWith(['0', 500, 500], "string cap '0' means no cap")]
    #[TestWith(['-10', 500, 500], 'negative string cap means no cap')]
    #[TestWith(['', 500, 100], 'empty string cap falls back to the package default')]
    #[TestWith(['abc', 500, 100], 'non-numeric string cap falls back to the package default')]
    public function it_treats_a_string_config_cap_the_way_env_provides_it(string $maxPerPage, int $perPage, int $expected): void
    {
        config(['restify.pagination.max_per_page' => $maxPerPage]);

        $pagination = new PaginationDataObject(perPage: $perPage, page: null);

        $this->assertSame($expected, $pagination->resolvePerPage(15));
    }

    #[Test]
    public function a_false_config_cap_means_no_cap(): void
    {
        config(['restify.pagination.max_per_page' => false]);

        $pagination = new PaginationDataObject(perPage: 9999, page: null);

        $this->assertSame(9999, $pagination->resolvePerPage(15));
    }

    #[Test]
    #[TestWith(['3', 3], 'numeric string page is cast to int')]
    #[TestWith([2, 2], 'int page is returned as is')]
    #[TestWith([null, null], 'absent page resolves to null')]
    #[TestWith(['abc', null], 'non-numeric page resolves to null')]
    #[TestWith([0, null], 'zero page resolves to null')]
    #[TestWith([-1, null], 'negative page resolves to null')]
    public function it_resolves_the_page_number(int|string|null $page, ?int $expected): void
    {
        $pagination = new PaginationDataObject(perPage: null, page: $page);

        $this->assertSame($expected, $pagination->resolvePage());
    }
}
