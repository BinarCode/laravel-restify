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

            // This approach could be rewritten using join.
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
            return $query->orWhereRaw("UPPER({$this->column}) LIKE '%".strtoupper($value)."%'");
        }

        return $query->orWhere($this->column, $likeOperator, '%'.$value.'%');
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
