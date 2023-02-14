<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class SortableFilter extends Filter
{
    public static $uriKey = 'sortables';

    public string $direction = 'asc';

    private HasOne|BelongsTo $relation;

    private Closure $resolver;

    public const TYPE = 'sortable';

    /**
     * @param  Builder  $query
     * @param  string  $value
     * @return Builder
     */
    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        if (isset($this->resolver) && is_callable($this->resolver)) {
            return call_user_func($this->resolver, $request, $query, $value);
        }

        if (isset($this->relation)) {
            if (! $this->relation->authorize($request)) {
                return $query;
            }

            // This approach could be rewritten using join.
            $query->orderBy(
                $this->relation->getRelatedModel($this->repository)::select($this->qualifyColumn())
                    ->whereColumn(
                        $this->relationForeignColumn(),
                        $this->relationRelatedColumn(),
                    )
                    ->orderBy($this->qualifyColumn(), $value)
                    ->take(1),
                $value
            );

            return $query;
        }

        $query->orderBy($this->column, $value);
    }

    public function usingRelation(HasOne|BelongsTo $field): self
    {
        $this->relation = $field;

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

        return $this->relation;
    }

    public function hasEager(): bool
    {
        return isset($this->relation) && $this->relation instanceof EagerField;
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

            $this->column = $tablePlural.'.'.Str::after($this->column, '.');
        }

        return $this;
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

    private function relationForeignColumn(): string
    {
        if ($this->relation instanceof HasOne) {
            return $this->relation->getRelation($this->repository)->getQualifiedForeignKeyName();
        }

        return $this->relation->getQualifiedKey($this->repository);
    }

    private function relationRelatedColumn(): string
    {
        if ($this->relation instanceof HasOne) {
            return $this->relation->getRelation($this->repository)->getQualifiedParentKeyName();
        }

        return $this->relation->getRelatedKey($this->repository);
    }
}
