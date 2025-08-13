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

            $relatedModel = $this->belongsToField->getRelatedModel($this->repository);
            $relatedTable = $relatedModel->getTable();
            $localKey = $this->belongsToField->getRelatedKey($this->repository);
            $foreignKey = $this->belongsToField->getQualifiedKey($this->repository);

            // Check if we already joined this table
            $hasJoin = collect($query->getQuery()->joins ?? [])->contains(function ($join) use ($relatedTable, $localKey, $foreignKey) {
                return $join->table === $relatedTable
                    && collect($join->wheres)->contains(function ($where) use ($localKey, $foreignKey) {
                        return isset($where['first']) && isset($where['second'])
                            && $where['first'] === $foreignKey
                            && $where['second'] === $localKey;
                    });
            });

            if (! $hasJoin) {
                $query->leftJoin($relatedTable, $foreignKey, '=', $localKey);
            }

            // Apply search conditions using the joined table
            collect($this->belongsToField->getSearchables())->each(function (string $attribute) use ($query, $relatedTable, $likeOperator, $value) {
                $columnName = $relatedTable.'.'.$attribute;

                if (! config('restify.search.case_sensitive')) {
                    $query->orWhereRaw("UPPER({$columnName}) LIKE ?", ['%'.strtoupper($value).'%']);
                } else {
                    $query->orWhere($columnName, $likeOperator, "%{$value}%");
                }
            });

            return $query;
        }

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
}
