<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SearchableFilter extends Filter
{
    public const TYPE = 'searchable';

    public array $computedColumns = [];

    private BelongsTo $belongsToField;

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
            ray('Searching through BelongsTo relation');
            if (! $this->belongsToField->authorize($request)) {
                ray('BelongsTo field not authorized for this request, skipping search.');

                return $query;
            }

            // TODO: This approach could be optimized using JOIN instead of subquery for better performance
            // Current implementation uses subqueries which work correctly but may be slower for large datasets
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
}
