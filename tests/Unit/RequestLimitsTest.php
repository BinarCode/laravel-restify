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
    public function the_shipped_config_sets_no_page_or_bulk_limit_and_the_default_related_depth(): void
    {
        $this->assertNull(RequestLimits::maxPerPage());
        $this->assertNull(RequestLimits::maxBulkRows());
        $this->assertSame(5, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    public function a_published_config_that_predates_the_keys_sets_no_page_or_bulk_limit_and_the_default_related_depth(): void
    {
        config(['restify' => Arr::except(config('restify'), ['pagination', 'bulk', 'related'])]);

        $this->assertNull(config('restify.pagination.max_per_page'));
        $this->assertNull(config('restify.bulk.max_rows'));
        $this->assertNull(config('restify.related.max_depth'));

        $this->assertNull(RequestLimits::maxPerPage());
        $this->assertNull(RequestLimits::maxBulkRows());
        $this->assertSame(5, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    #[TestWith([null, 5], 'null: the package default')]
    #[TestWith(['', 5], 'empty string, as an empty env value: the package default')]
    #[TestWith(['abc', 5], 'non-numeric string: the package default')]
    #[TestWith([true, 5], 'true: the package default')]
    #[TestWith([[], 5], 'array: the package default')]
    #[TestWith([INF, 5], 'infinite float: the package default')]
    #[TestWith([3, 3], 'positive int: that limit')]
    #[TestWith(['250', 250], 'numeric string, as env() provides it: that limit')]
    #[TestWith([250.0, 250], 'float: that limit')]
    #[TestWith([1e20, PHP_INT_MAX], 'float that does not fit an int: saturates, not a crash')]
    #[TestWith([0, null], '0: no limit')]
    #[TestWith(['0', null], "'0': no limit")]
    #[TestWith([-1, null], 'negative: no limit')]
    #[TestWith([false, null], 'false: no limit')]
    public function it_reads_the_related_depth_limit_from_config(mixed $configured, ?int $expected): void
    {
        config(['restify.related.max_depth' => $configured]);

        $this->assertSame($expected, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    #[TestWith([null, null], 'null, as an unset environment variable resolves: no limit')]
    #[TestWith(['', null], 'empty string, as an empty env value: no limit')]
    #[TestWith(['abc', null], 'non-numeric string: no limit')]
    #[TestWith([true, null], 'true: no limit')]
    #[TestWith([[], null], 'array: no limit')]
    #[TestWith([INF, null], 'infinite float: no limit')]
    #[TestWith([0, null], '0: no limit')]
    #[TestWith(['0', null], "'0': no limit")]
    #[TestWith([-1, null], 'negative: no limit')]
    #[TestWith([false, null], 'false: no limit')]
    #[TestWith([50, 50], 'positive int: that limit')]
    #[TestWith(['500', 500], 'numeric string, as env() provides it: that limit')]
    #[TestWith([250.0, 250], 'float: that limit')]
    #[TestWith([1e20, PHP_INT_MAX], 'float that does not fit an int: saturates, not a crash')]
    public function it_reads_the_opt_in_page_and_bulk_limits_from_config(mixed $configured, ?int $expected): void
    {
        config([
            'restify.pagination.max_per_page' => $configured,
            'restify.bulk.max_rows' => $configured,
        ]);

        $this->assertSame($expected, RequestLimits::maxPerPage());
        $this->assertSame($expected, RequestLimits::maxBulkRows());
    }
}
