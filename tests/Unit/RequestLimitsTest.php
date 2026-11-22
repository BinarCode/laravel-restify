<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Http\Requests\RequestLimits;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class RequestLimitsTest extends IntegrationTestCase
{
    #[Test]
    public function the_shipped_config_resolves_to_no_page_cap_and_the_package_defaults(): void
    {
        $this->assertNull(RequestLimits::maxPerPage());
        $this->assertSame(1000, RequestLimits::maxBulkRows());
        $this->assertSame(5, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    public function a_published_config_that_predates_the_keys_has_no_page_cap_and_gets_the_package_defaults(): void
    {
        config(['restify' => Arr::except(config('restify'), ['pagination', 'bulk', 'related'])]);

        $this->assertNull(config('restify.pagination.max_per_page'));
        $this->assertNull(config('restify.bulk.max_rows'));
        $this->assertNull(config('restify.related.max_depth'));

        $this->assertNull(RequestLimits::maxPerPage());
        $this->assertSame(1000, RequestLimits::maxBulkRows());
        $this->assertSame(5, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    #[TestWith([null, 1000, 5], 'null: the package default')]
    #[TestWith(['', 1000, 5], 'empty string, as an empty env value: the package default')]
    #[TestWith(['abc', 1000, 5], 'non-numeric string: the package default')]
    #[TestWith([true, 1000, 5], 'true: the package default')]
    #[TestWith([[], 1000, 5], 'array: the package default')]
    #[TestWith([INF, 1000, 5], 'infinite float: the package default')]
    #[TestWith([3, 3, 3], 'positive int: that limit')]
    #[TestWith(['250', 250, 250], 'numeric string, as env() provides it: that limit')]
    #[TestWith([250.0, 250, 250], 'float: that limit')]
    #[TestWith([1e20, PHP_INT_MAX, PHP_INT_MAX], 'float that does not fit an int: saturates, not a crash')]
    #[TestWith([0, null, null], '0: no limit')]
    #[TestWith(['0', null, null], "'0': no limit")]
    #[TestWith([-1, null, null], 'negative: no limit')]
    #[TestWith([false, null, null], 'false: no limit')]
    public function it_reads_the_bulk_and_related_limits_from_config(
        mixed $configured,
        ?int $expectedBulkRows,
        ?int $expectedRelatedDepth,
    ): void {
        config([
            'restify.bulk.max_rows' => $configured,
            'restify.related.max_depth' => $configured,
        ]);

        $this->assertSame($expectedBulkRows, RequestLimits::maxBulkRows());
        $this->assertSame($expectedRelatedDepth, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    #[TestWith([null, null], 'null, as an unset RESTIFY_MAX_PER_PAGE resolves: no cap')]
    #[TestWith(['', null], 'empty string, as an empty env value: no cap')]
    #[TestWith(['abc', null], 'non-numeric string: no cap')]
    #[TestWith([true, null], 'true: no cap')]
    #[TestWith([[], null], 'array: no cap')]
    #[TestWith([INF, null], 'infinite float: no cap')]
    #[TestWith([0, null], '0: no cap')]
    #[TestWith(['0', null], "'0': no cap")]
    #[TestWith([-1, null], 'negative: no cap')]
    #[TestWith([false, null], 'false: no cap')]
    #[TestWith([50, 50], 'positive int: that cap')]
    #[TestWith(['500', 500], 'numeric string, as env() provides it: that cap')]
    #[TestWith([250.0, 250], 'float: that cap')]
    #[TestWith([1e20, PHP_INT_MAX], 'float that does not fit an int: saturates, not a crash')]
    public function it_reads_the_per_page_cap_from_config(mixed $configured, ?int $expected): void
    {
        config(['restify.pagination.max_per_page' => $configured]);

        $this->assertSame($expected, RequestLimits::maxPerPage());
    }
}
