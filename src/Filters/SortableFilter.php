<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class SortableFilter extends Filter
{
    public static $uriKey = 'sortables';

    public string $direction = 'asc';

    private BelongsTo $belongsToField;

    private Closure $resolver;

    public const TYPE = 'sortable';

    /**
     * @param  RestifyRequest  $request
     * @param  Builder  $query
     * @param  string  $value
     * @return Builder
     */
    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        if (isset($this->resolver) && is_callable($this->resolver)) {
            return call_user_func($this->resolver, $request, $query, $value);
        }

        if (isset($this->belongsToField)) {
            if (!$this->belongsToField->authorize($request)) {
                return $query;
            }

            // This approach could be rewritten using join.
            $query->orderBy(
                $this->belongsToField->getRelatedModel($this->repository)::select($this->qualifyColumn())
                    ->whereColumn(
                        $this->belongsToField->getQualifiedKey($this->repository),
                        $this->belongsToField->getRelatedKey($this->repository)
                    )
                    ->orderBy($this->qualifyColumn(), $value)
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

    public function getSortableEager(): ?Sortable
    {
        if (! $this->hasEager()) {
            return null;
        }

        if (! $this->getEager() instanceof Sortable) {
            return null;
        }

        return $this->getEager();
    }

    public function getEager(): EagerField|Sortable|null
    {
        if (! $this->hasEager()) {
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

    public function resolveFrontendColumn(): self
    {
        if (Str::contains($this->column, '.')) {
            /**
             * We assume that the name is singular, as we related sort by
             * has one or belongs to relationships.
             *
             * user.attributes.name => users.attributes.name
             */
            $tablePlural = Str::plural(Str::before($this->column, '.'));

            $this->column =  $tablePlural . '.' . Str::after($this->column, '.');
        }

        return $this;
    }

    public function syncDirection(string $direction = null): self
    {
        if (!is_null($direction) && in_array($direction, ['asc', 'desc'])) {
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
