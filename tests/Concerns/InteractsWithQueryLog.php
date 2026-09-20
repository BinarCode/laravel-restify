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
    protected function setUpInteractsWithQueryLog(): void
    {
        DB::enableQueryLog();
    }

    protected function tearDownInteractsWithQueryLog(): void
    {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    /**
     * Start counting here, once the fixtures are in place.
     */
    protected function recordQueries(): void
    {
        DB::flushQueryLog();
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
    protected function selectsAgainst(string $model): array
    {
        $table = $this->getTable($model);

        return array_values(array_filter(
            $this->executedQueries(),
            fn (string $query): bool => str_starts_with($query, 'select ')
                && str_contains($query, 'from "'.$table.'"'),
        ));
    }

    /**
     * @return list<string>
     */
    protected function executedWrites(): array
    {
        return array_values(array_filter(
            $this->executedQueries(),
            fn (string $query): bool => str_starts_with($query, 'insert ')
                || str_starts_with($query, 'update ')
                || str_starts_with($query, 'delete '),
        ));
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected function assertSelectCount(int $expected, string $model): void
    {
        $this->assertCount(
            $expected,
            $this->selectsAgainst($model),
            'unexpected selects against ['.$model.']: '
                .json_encode($this->executedQueries(), JSON_PRETTY_PRINT),
        );
    }
}
