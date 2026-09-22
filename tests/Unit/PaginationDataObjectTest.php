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
    #[TestWith([20, null, 20], 'requested value used when under the default')]
    #[TestWith([null, 15, 15], 'default used when perPage is absent')]
    #[TestWith(['abc', 15, 15], 'default used when perPage is non-numeric')]
    #[TestWith([0, 15, 15], 'default used when perPage is zero')]
    #[TestWith([-5, 15, 15], 'default used when perPage is negative')]
    #[TestWith(['20', null, 20], 'numeric string perPage is honoured')]
    public function it_resolves_per_page_without_a_cap(int|string|null $perPage, ?int $default, int $expected): void
    {
        config(['restify.pagination.max_per_page' => null]);

        $pagination = new PaginationDataObject(perPage: $perPage, page: null);

        $this->assertSame($expected, $pagination->resolvePerPage($default ?? 15));
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
