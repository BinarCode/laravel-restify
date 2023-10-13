<?php

namespace Binaryk\LaravelRestify\Filters\Sorts;

class NaturalSortFilter
{
    public function __invoke($request, $query, $value, string $column): void
    {
        $query->orderByRaw("CAST({$column} AS UNSIGNED) {$value}, {$column} {$value}");
    }
}
