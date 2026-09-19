<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\TestCase;

/**
 * @mixin TestCase
 */
trait InteractsWithQueryLog
{
    protected function recordQueries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
    }

    /**
     * @return list<string>
     */
    protected function executedQueries(): array
    {
        return array_column(DB::getQueryLog(), 'query');
    }

    /**
     * @return list<string>
     */
    protected function queriesAgainst(string $table): array
    {
        return array_values(array_filter(
            $this->executedQueries(),
            fn (string $query): bool => str_contains($query, 'from "'.$table.'"'),
        ));
    }

    protected function assertQueryCountAgainst(int $expected, string $table): void
    {
        $this->assertCount(
            $expected,
            $this->queriesAgainst($table),
            'unexpected number of queries against "'.$table.'". executed: '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );
    }
}
