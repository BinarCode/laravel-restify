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
    public function the_shipped_config_resolves_to_the_package_defaults(): void
    {
        $this->assertSame(100, RequestLimits::maxPerPage(15));
        $this->assertSame(1000, RequestLimits::maxBulkRows());
        $this->assertSame(5, RequestLimits::maxRelatedDepth());
    }

    #[Test]
    public function a_published_config_that_predates_the_keys_still_gets_the_package_defaults(): void
    {
        config(['restify' => Arr::except(config('restify'), ['pagination', 'bulk', 'related'])]);

        $this->assertNull(config('restify.pagination.max_per_page'));
        $this->assertNull(config('restify.bulk.max_rows'));
        $this->assertNull(config('restify.related.max_depth'));

        $this->assertSame(100, RequestLimits::maxPerPage(15));
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
    #[TestWith([null, 15, 100], 'not configured: the package default')]
    #[TestWith([null, 100, 100], 'not configured, repository default equal to the package default')]
    #[TestWith([null, 250, 250], 'not configured: never below the repository default')]
    #[TestWith(['', 250, 250], 'empty string counts as not configured')]
    #[TestWith(['abc', 15, 100], 'non-numeric string counts as not configured')]
    #[TestWith([50, 15, 50], 'configured below the package default: that limit')]
    #[TestWith([500, 15, 500], 'configured above the package default: that limit')]
    #[TestWith(['500', 15, 500], 'numeric string, as env() provides it: that limit')]
    #[TestWith([50, 250, 50], 'configured: applies as is, also below the repository default')]
    #[TestWith([0, 250, null], '0: no limit')]
    #[TestWith([-1, 15, null], 'negative: no limit')]
    #[TestWith([false, 15, null], 'false: no limit')]
    public function it_reads_the_per_page_limit_from_config(mixed $configured, int $repositoryDefault, ?int $expected): void
    {
        config(['restify.pagination.max_per_page' => $configured]);

        $this->assertSame($expected, RequestLimits::maxPerPage($repositoryDefault));
    }
}
