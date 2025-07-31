<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SearchableFilter extends Filter
{
    public const TYPE = 'searchable';

    public array $computedColumns = [];

    private BelongsTo $belongsToField;

    public function filter(RestifyRequest $request, $query, $value)
    {
        $connectionType = $this->repository->model()->getConnection()->getDriverName();

        $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

        if (isset($this->belongsToField)) {
            if (! $this->belongsToField->authorize($request)) {
                return $query;
            }

            // Use JOIN optimization only if enabled in config and we have a BelongsTo relationship
            if (config('restify.search.use_joins', false)) {
                $this->applyJoinSearchConditions($query, $likeOperator, $value);
            } else {
                $this->applySubquerySearchConditions($query, $likeOperator, $value);
            }

            return $query;
        }

        // For direct field searches (non-relationship), always use the simple approach
        if (! config('restify.search.case_sensitive')) {
            $upper = strtoupper($value);

            return $query->orWhereRaw("UPPER({$this->column}) LIKE ?", ['%'.$upper.'%']);
        }

        return $query->orWhere($this->column, $likeOperator, "%{$value}%");
    }

    public function usingBelongsTo(BelongsTo $field): self
    {
        $this->belongsToField = $field;

        return $this;
    }

    public function computed(...$columns): self
    {
        $this->computedColumns = collect($columns)->flatten()->all();

        return $this;
    }

    protected function applyJoinSearchConditions($query, $likeOperator, $value)
    {
        $relatedModel = $this->belongsToField->getRelatedModel($this->repository);
        $relatedTable = $relatedModel->getTable();
        $parentTable = $this->repository->model()->getTable();

        $localKey = $this->belongsToField->getQualifiedKey($this->repository);
        $foreignKeyName = $relatedModel->getKeyName();

        // Use a consistent alias based on the relationship name to reuse joins
        $relationshipName = $this->belongsToField->getAttribute();
        $joinAlias = "{$relatedTable}_for_{$relationshipName}";

        // Check if this exact join already exists
        $existingJoins = collect($query->getQuery()->joins ?? []);
        $joinExists = $existingJoins->contains(function ($join) use ($joinAlias, $relatedTable) {
            return $join->table === "{$relatedTable} as {$joinAlias}" ||
                   str_contains($join->table, $joinAlias);
        });

        if (! $joinExists) {
            $query->leftJoin("{$relatedTable} as {$joinAlias}", function ($join) use ($localKey, $joinAlias, $foreignKeyName) {
                $join->on($localKey, '=', "{$joinAlias}.{$foreignKeyName}");
            });
        }

        // Apply search conditions for each searchable attribute
        collect($this->belongsToField->getSearchables())->each(function (string $attribute) use ($query, $likeOperator, $value, $joinAlias) {
            $qualifiedAttribute = "{$joinAlias}.{$attribute}";

            if (! config('restify.search.case_sensitive')) {
                $upper = strtoupper($value);
                $query->orWhereRaw("UPPER({$qualifiedAttribute}) LIKE ?", ['%'.$upper.'%']);
            } else {
                $query->orWhere($qualifiedAttribute, $likeOperator, "%{$value}%");
            }
        });
    }

    protected function applySubquerySearchConditions($query, $likeOperator, $value)
    {
        // Original implementation using subqueries for backward compatibility
        collect($this->belongsToField->getSearchables())->each(function (string $attribute) use ($query, $likeOperator, $value) {
            $query->orWhere(
                $this->belongsToField->getRelatedModel($this->repository)::select($attribute)
                    ->whereColumn(
                        $this->belongsToField->getQualifiedKey($this->repository),
                        $this->belongsToField->getRelatedKey($this->repository)
                    )
                    ->take(1),
                $likeOperator,
                "%{$value}%"
            );
        });
    }
}
