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

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class RelatedCollection extends Collection
{
    public function intoAssoc(): self
    {
        return $this->mapWithKeys(function ($value, $key) {
            $mapKey = is_numeric($key) ? $value : $key;

            if ($value instanceof EagerField) {
                $mapKey = (is_numeric($key) || empty($key)) ? $value->getAttribute() : $key;
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
        return $this->filter(function (mixed $repositoryRelatedField, $repositoryRelatedKey) use (
            $request,
            $repository
        ) {
            if ($repositoryRelatedField instanceof EagerField) {
                if ($repository->getEagerParent()) {
                    $relatedKey = $repository->getEagerParent().'.'.$repositoryRelatedKey;
                } elseif ($request->related()->rootKey === $repository::uriKey()) {
                    $relatedKey = $repository::uriKey().'.'.$repositoryRelatedKey;
                } else {
                    $relatedKey = $repositoryRelatedKey;
                }

                $relatedQuery = $request->related()->getRelatedQueryFor($relatedKey);

                if ($relatedQuery) {
                    $repositoryRelatedField->withRelatedQuery($relatedQuery);
                }

                return (bool) $relatedQuery;
            }

            // might be a closure or a normal relationship
            return $request->related()->hasRelation($repository::uriKey().'.'.$repositoryRelatedKey);
        });
    }

    public function mapIntoRelated(RestifyRequest $request, Repository $repository): self
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
                ->columns($request->related()->getColumnsFor($repository::uriKey().'.'.$related->getRelation()))
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

    public function forRequest(RestifyRequest $request, Repository $repository): self
    {
        if (! $request->related()->hasRelated()) {
            return self::make([]);
        }

        return $this
            ->intoAssoc()
            ->authorized($request)
            ->inRequest($request, $repository)
            ->when($request->isShowRequest(), fn (self $collection) => $collection->forShow($request, $repository))
            ->when($request->isIndexRequest(), fn (self $collection) => $collection->forIndex($request, $repository));
    }

    public function unserialized(RestifyRequest $request, Repository $repository)
    {
        return $this->filter(function (Related $related) use ($request, $repository) {
            return ! in_array(
                $related->uniqueIdentifierForRepository($repository),
                $request->related()->resolvedRelationships,
                true
            );
        });
    }

    public function markQuerySerialized(RestifyRequest $request, Repository $repository): self
    {
        return $this->each(function (Related $related) {
            //            dd($related->getValue());
            $related->relatedQuery?->serialized();

            return $related;
        });
    }
}
