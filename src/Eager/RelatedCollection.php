<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\Contracts\Sortable;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\HasOne;
use Binaryk\LaravelRestify\Fields\MorphToMany;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RelatedCollection extends Collection
{
    public function intoAssoc(): self
    {
        return $this->mapWithKeys(function ($value, $key) {
            $mapKey = is_numeric($key) ? $value : $key;

            if ($value instanceof EagerField) {
                $mapKey = $key ?: $value->getAttribute();
            }

            return [
                $mapKey => $value,
            ];
        });
    }

    public function forEager(RestifyRequest $request): self
    {
        return $this
            ->filter(fn ($value, $key) => $value instanceof EagerField)
            ->filter(fn (Field $field) => $field->authorize($request))
            ->unique('attribute');
    }

    public function forManyToManyRelations(RestifyRequest $request): self
    {
        return $this->filter(function ($field) {
            return $field instanceof BelongsToMany || $field instanceof MorphToMany;
        })->filter(fn (EagerField $field) => $field->authorize($request));
    }

    public function forBelongsToRelations(RestifyRequest $request): self
    {
        return $this->filter(function ($field) {
            return $field instanceof BelongsTo;
        })->filter(fn (EagerField $field) => $field->authorize($request));
    }

    public function mapIntoSortable(): self
    {
        return $this
            ->filter(fn ($key) => $key instanceof Sortable)
            ->filter(fn (Sortable $field) => $field->isSortable())
            ->map(function (Sortable $field) {
                $filter = SortableFilter::make();

                if ($field instanceof BelongsTo || $field instanceof HasOne) {
                    return $filter->usingRelation($field)->setColumn($field->qualifySortable());
                }

                return null;
            })->filter();
    }

    public function forShow(RestifyRequest $request, Repository $repository): self
    {
        return $this->filter(function ($related) use ($request, $repository) {
            if ($related instanceof Field) {
                return $related->isShownOnShow($request, $repository);
            }

            return $related;
        });
    }

    public function forIndex(RestifyRequest $request, Repository $repository): self
    {
        return $this->filter(function ($related) use ($request, $repository) {
            if ($related instanceof Field) {
                return $related->isShownOnIndex($request, $repository);
            }

            return $related;
        });
    }

    public function inRequest(RestifyRequest $request, Repository $repository): self
    {
        $queryRelated = collect($request->related()->related)
            ->transform(fn ($related) => Str::before($related, '['))
            ->filter(fn ($related) => ! in_array($repository::uriKey() . $repository->getKey() . $related, $request->related()->resolvedRelationships, true))
            ->all();

        return $this
            ->filter(fn ($field, $key) => in_array($key, $queryRelated))
            ->unique();
    }

    public function mapIntoRelated(RestifyRequest $request): self
    {
        return $this->map(function ($value, $key) {
            return tap(
                Related::make($key, $value instanceof EagerField ? $value : null),
                function (Related $related) use ($value) {
                    if (is_callable($value)) {
                        $related->resolveUsing($value);
                    }
                }
            );
        })->map(
            fn (Related $related) => $related
            ->columns($request->related()->getColumnsFor($related->getRelation()))
            ->nested($request->related()->getNestedFor($related->getRelation()))
        );
    }

    public function authorized(RestifyRequest $request)
    {
        return $this
            ->intoAssoc()
            ->filter(fn ($key, $value) => $key instanceof EagerField ? $key->authorize($request) : true);
    }

    public function onlySearchable(RestifyRequest $request): self
    {
        return $this->forBelongsToRelations($request)
            ->filter(fn (BelongsTo $field) => $field->isSearchable());
    }
}
