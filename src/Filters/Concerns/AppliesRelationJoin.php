<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Filters\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait AppliesRelationJoin
{
    protected function ensureLeftJoin(
        Builder|Relation $query,
        string $relatedTable,
        string $localQualifiedKey,
        string $relatedQualifiedKey,
        string $mainTable,
    ): void {
        // Add LEFT JOIN only if a left join on the same table hasn't been added already.
        // Match join type so an upstream INNER JOIN doesn't suppress our LEFT JOIN — the two
        // have different semantics (INNER drops rows with no match; LEFT keeps them).
        $exists = collect($query->toBase()->joins ?? [])
            ->contains(static fn ($join): bool => $join->table === $relatedTable && $join->type === 'left');

        if ($exists) {
            return;
        }

        // Ensure we only select columns from the main table to avoid column conflicts.
        // Only set select if it hasn't been set already
        if (empty($query->getQuery()->columns)) {
            $query->select([$mainTable.'.*']);
        }

        $query->leftJoin($relatedTable, $localQualifiedKey, '=', $relatedQualifiedKey);
    }
}
