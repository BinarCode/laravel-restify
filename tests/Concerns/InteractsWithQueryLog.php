<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

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
     * @param  class-string<Model>  $model
     * @return list<string>
     */
    protected function queriesAgainst(string $model): array
    {
        $table = $this->getTable($model);

        return array_values(array_filter(
            $this->executedQueries(),
            fn (string $query): bool => str_contains($query, 'from "'.$table.'"'),
        ));
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected function assertQueryCountAgainst(int $expected, string $model): void
    {
        $this->assertCount(
            $expected,
            $this->queriesAgainst($model),
            'unexpected number of queries against ['.$model.']. executed: '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );
    }
}
