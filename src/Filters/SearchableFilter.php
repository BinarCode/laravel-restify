<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SearchableFilter extends Filter
{
    public const TYPE = 'searchable';

    public array $computedColumns = [];

    public BelongsTo $belongsToField;

    protected $customClosure = null;

    public function filter(RestifyRequest $request, $query, $value)
    {
        // Use custom closure if available
        if ($this->customClosure) {
            return call_user_func($this->customClosure, $request, $query, $value);
        }

        $connectionType = $this->repository->model()->getConnection()->getDriverName();

        $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

        if (isset($this->belongsToField)) {
            if (! $this->belongsToField->authorize($request)) {
                return $query;
            }

            // Check if JOINs are enabled in config
            if (config('restify.search.use_joins_for_belongs_to', false)) {
                // JOINs are applied at the service level, so we just need to apply search conditions
                $relatedModel = $this->belongsToField->getRelatedModel($this->repository);
                $relatedTable = $relatedModel->getTable();

                // Apply search conditions using qualified column names from the joined table
                collect($this->belongsToField->getSearchables())->each(function (string $attribute) use ($query, $likeOperator, $value, $relatedTable) {
                    // Check if the attribute is already qualified (contains a dot)
                    $qualifiedColumn = str_contains($attribute, '.')
                        ? $attribute
                        : $relatedTable . '.' . $attribute;

                    $query->orWhere($qualifiedColumn, $likeOperator, "%{$value}%");
                });
            } else {
                // Use the original subquery approach when JOINs are disabled
                collect($this->belongsToField->getSearchables())->each(function (string $attribute) use ($query, $likeOperator, $value) {
                    $query->orWhere(function ($subQuery) use ($attribute, $likeOperator, $value) {
                        $relation = $this->belongsToField->getRelation($this->repository);
                        $relatedModel = $this->belongsToField->getRelatedModel($this->repository);
                        $relatedTable = $relatedModel->getTable();
                        $foreignKey = $relation->getForeignKeyName();
                        $ownerKey = $relation->getOwnerKeyName();

                        // Build the subquery: (SELECT column FROM related_table WHERE related_table.key = main_table.foreign_key LIMIT 1)
                        $qualifiedColumn = str_contains($attribute, '.') ? $attribute : $relatedTable . '.' . $attribute;
                        $localTableForeignKey = $this->repository->model()->getTable() . '.' . $foreignKey;
                        $relatedTableOwnerKey = $relatedTable . '.' . $ownerKey;

                        $subQuery->whereRaw(
                            "(SELECT {$qualifiedColumn} FROM {$relatedTable} WHERE {$relatedTableOwnerKey} = {$localTableForeignKey} LIMIT 1) {$likeOperator} ?",
                            ["%{$value}%"]
                        );
                    });
                });
            }

            return $query;
        }

        if (! config('restify.search.case_sensitive')) {
            $upper = strtoupper($value);

            $columnExpression = $connectionType === 'pgsql'
                ? "UPPER({$this->column}::text)"
                : "UPPER({$this->column})";

            return $query->orWhereRaw("{$columnExpression} LIKE ?", ['%'.$upper.'%']);
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

    public function usingClosure(callable $closure): self
    {
        $this->customClosure = $closure;

        return $this;
    }

    public function hasCustomClosure(): bool
    {
        return ! is_null($this->customClosure);
    }

    public function hasBelongsTo(): bool
    {
        return isset($this->belongsToField);
    }
}
