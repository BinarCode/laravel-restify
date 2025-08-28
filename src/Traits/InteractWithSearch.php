<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Eager\RelatedCollection;
use Binaryk\LaravelRestify\Fields;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Filters\AdvancedFiltersCollection;
use Binaryk\LaravelRestify\Filters\Filter;
use Binaryk\LaravelRestify\Filters\MatchesCollection;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SearchablesCollection;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Filters\SortCollection;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

/**
 * @mixin Repository
 */
trait InteractWithSearch
{
    use AuthorizableModels;

    public static int $defaultPerPage = 15;

    public static int $defaultRelatablePerPage = 15;

    public static function searchables(): array
    {
        return static::$search;
    }

    public static function withs(): array
    {
        return static::$with ?? [];
    }

    public static function collectWiths(RestifyRequest $request, Repository $repository): Collection
    {
        return collect(array_unique(array_merge(
            $repository::withs(),
            static::lazyLoadedFieldsRelationship($request, $repository),
        )));
    }

    public static function lazyLoadedFieldsRelationship(RestifyRequest $request, Repository $repository): array
    {
        return $repository->collectFields($request)
            ->filter(fn(Field $field) => $field->isLazy($request))
            ->map(fn(Field $field) => $field->getLazyRelationshipName())
            ->all();
    }

    public static function related(): array
    {
        return static::$related ?? [];
    }

    public static function include(): array
    {
        return static::related();
    }

    public static function collectRelated(): RelatedCollection
    {
        return RelatedCollection::make(static::include());
    }

    public static function matches(): array
    {
        return empty(static::$match)
            ? [static::newModel()->getKeyName()]
            : static::$match;
    }

    public static function sorts(): array
    {
        return empty(static::$sort)
            ? [static::newModel()->getQualifiedKeyName()]
            : static::$sort;
    }

    public static function collectSortables(RestifyRequest $request, Repository $repository): SortCollection
    {
        return static::collectSorts($request, $repository);
    }

    /**
     * @deprecated use collectSortables instead
     */
    public static function collectSorts(RestifyRequest $request, Repository $repository): SortCollection
    {
        $fieldSorts = static::collectFieldSorts($request, $repository);

        $requestSorts = (new SortCollection(explode(',', $request->input('sort', ''))))
            ->normalize()
            ->hydrateDefinition($repository, $request)
            ->authorized($request)
            ->inRepository($request, $repository)
            ->hydrateRepository($repository);

        // Merge field sorts with request sorts and ensure it stays a SortCollection
        return new SortCollection($requestSorts->merge($fieldSorts));
    }

    public static function collectFieldSorts(RestifyRequest $request, Repository $repository): Collection
    {
        return $repository->collectFields($request)
            ->filter(fn(Field $field) => $field->isSortable($request))
            ->map(function (Field $field) {
                $sortableFilter = new SortableFilter;
                $sortableFilter->setColumn($field->getAttribute());

                return $sortableFilter;
            });
    }

    public static function collectSearchables(RestifyRequest $request, Repository $repository): SearchablesCollection
    {
        // Start with repository-level searchables
        $repositorySearchables = $repository::searchables();

        // Collect field-level searchables
        $fieldSearchables = static::collectFieldSearchables($request, $repository);

        // Collect BelongsTo searchable relations
        $belongsToSearchables = $repository::collectRelated()
            ->onlySearchable($request)
            ->map(function (Fields\BelongsTo $field) use ($repository) {
                return SearchableFilter::make()
                    ->setRepository($repository)
                    ->usingBelongsTo($field);
            });

        // Merge all searchables into a unified collection
        $allSearchables = collect($repositorySearchables)
            ->merge($fieldSearchables)
            ->merge($belongsToSearchables);

        return (new SearchablesCollection($allSearchables->all()))
            ->setRepository($repository)
            ->qualifyColumns($repository->model());
    }

    public static function collectFieldSearchables(RestifyRequest $request, Repository $repository): Collection
    {
        return $repository->collectFields($request)
            ->filter(fn(Field $field) => $field->isSearchable($request))
            ->map(function (Field $field) use ($request, $repository) {
                $searchColumn = $field->getSearchColumn($request);
                if ($searchColumn instanceof SearchableFilter) {
                    $searchColumn->setRepository($repository);
                    // Ensure the SearchableFilter has a column set
                    if (! $searchColumn->column()) {
                        $searchColumn->setColumn($field->getAttribute());
                    }

                    return $searchColumn;
                }

                $searchFilter = new SearchableFilter;
                $searchFilter->setRepository($repository);

                if (is_callable($searchColumn)) {
                    $searchFilter->setColumn($field->getAttribute());

                    return $searchFilter->usingClosure($searchColumn);
                }

                if (is_object($searchColumn) && method_exists($searchColumn, '__invoke')) {
                    $searchFilter->setColumn($field->getAttribute());

                    return $searchFilter->usingClosure($searchColumn);
                }

                $searchFilter->setColumn($field->getSearchColumn($request));

                return $searchFilter;
            });
    }

    public static function collectMatches(RestifyRequest $request, Repository $repository): MatchesCollection
    {
        $fieldMatches = static::collectFieldMatches($request, $repository);

        return (new MatchesCollection($repository::matches()))
            ->merge($fieldMatches)
            ->normalize()
            ->authorized($request)
            ->inQuery($request)
            ->hydrateDefinition($request, $repository);
    }

    public static function collectFieldMatches(RestifyRequest $request, Repository $repository): Collection
    {
        return $repository->collectFields($request)
            ->filter(fn(Field $field) => $field->isMatchable($request))
            ->map(callback: function (Field $field) use ($request) {
                $matchColumn = $field->getMatchColumn($request);
                if ($matchColumn instanceof MatchFilter) {
                    return $matchColumn;
                }

                $matchFilter = new MatchFilter;

                if (is_callable($matchColumn)) {
                    $matchFilter->setColumn($field->getAttribute());

                    return $matchFilter->usingClosure($matchColumn);
                }

                $matchFilter->setColumn($field->getMatchColumn($request));
                $matchFilter->setType($field->getMatchType($request));

                return $matchFilter;
            });
    }

    public static function collectFilters($type): Collection
    {
        $filters = collect([
            SearchableFilter::uriKey() => static::searchables(),
            MatchFilter::uriKey() => static::matches(),
            SortableFilter::uriKey() => static::sorts(),
        ])->get($type);

        /** * @var string $base */
        $base = collect([
            SearchableFilter::uriKey() => SearchableFilter::class,
            MatchFilter::uriKey() => MatchFilter::class,
            SortableFilter::uriKey() => SortableFilter::class,
        ])->get($type);

        if (! is_subclass_of($base, Filter::class)) {
            return collect([]);
        }

        return collect($filters)->map(function ($type, $column) use ($base) {
            if (is_numeric($column)) {
                /*
                 * This will handle for example searchables/sortables, where the definition is:
                 * $search = ['title']
                 * */
                $column = $type;
                $type = null;
            }

            return $type instanceof Filter
                ? tap($type, fn($filter) => $filter->column = $filter->column ?? $column)
                : tap(new $base, function (Filter $filter) use ($column, $type) {
                    $filter->type = $type ? $type : 'value';
                    $filter->column = $column;
                });
        })->values();
    }

    public function collectAdvancedFilters(RestifyRequest $request): AdvancedFiltersCollection
    {
        return AdvancedFiltersCollection::make($this->filters($request))->authorized($request);
    }

    abstract public function filters(RestifyRequest $request): array;
}
