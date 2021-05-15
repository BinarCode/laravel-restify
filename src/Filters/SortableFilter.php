<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SortableFilter extends Filter
{
    public static $uriKey = 'sortables';

    public string $direction = 'asc';

    private BelongsTo $belongsToField;

    private Closure $resolver;

    const TYPE = 'sortable';

    /**
     * @param RestifyRequest $request
     * @param Builder $query
     * @param string $value
     * @return Builder
     */
    public function filter(RestifyRequest $request, Builder $query, $value)
    {
        if (isset($this->resolver) && is_callable($this->resolver)) {
            return call_user_func($this->resolver, $request, $query, $value);
        }

        if (isset($this->belongsToField)) {
            if (! $this->belongsToField->authorize($request)) {
                return $query;
            }

            // This approach could be rewritten using join.
            $query->orderBy(
                $this->belongsToField->getRelatedModel($this->repository)::select($this->getColumn())
                ->whereColumn(
                    $this->belongsToField->getQualifiedKey($this->repository),
                    $this->belongsToField->getRelatedKey($this->repository)
                )
                ->orderBy($this->getColumn(), $value)
                ->take(1),
                $value
            );

            return $query;
        }

        $query->orderBy($this->column, $value);
    }

    public function usingBelongsTo(BelongsTo $field): self
    {
        $this->belongsToField = $field;

        return $this;
    }

    public function getEager(): ?EagerField
    {
        if (! isset($this->belongsToField)) {
            return null;
        }

        return $this->belongsToField;
    }

    public function hasEager(): bool
    {
        return isset($this->belongsToField) && $this->belongsToField instanceof EagerField;
    }

    public function asc(): self
    {
        $this->direction = 'asc';

        return $this;
    }

    public function desc(): self
    {
        $this->direction = 'desc';

        return $this;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function syncDirection(string $direction = null): self
    {
        if (! is_null($direction) && in_array($direction, ['asc', 'desc'])) {
            $this->direction = $direction;

            return $this;
        }

        if (Str::startsWith($this->column, '-')) {
            $this->desc();

            $this->column = Str::after($this->column, '-');

            return $this;
        }

        if (Str::startsWith($this->column, '+')) {
            $this->asc();

            $this->column = Str::after($this->column, '+');

            return $this;
        }

        return $this->asc();
    }

    public function usingClosure(Closure $closure): self
    {
        $this->resolver = $closure;

        return $this;
    }
}
