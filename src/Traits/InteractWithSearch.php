<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Eager\RelatedCollection;
use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Sort\SortCollection;
use Illuminate\Support\Collection;

trait InteractWithSearch
{
    use AuthorizableModels;

    public static $defaultPerPage = 15;

    public static $defaultRelatablePerPage = 15;

    public static function getSearchableFields()
    {
        return empty(static::$search)
            ? [static::newModel()->getKeyName()]
            : static::$search;
    }

    public static function getWiths()
    {
        return static::$withs ?? [];
    }

    /**
     * Use 'related' instead.
     *
     * @return array
     * @deprecated
     */
    public static function getRelated()
    {
        return static::$related ?? [];
    }

    public static function related(): array
    {
        return static::$related ?? [];
    }

    public static function collectRelated(): RelatedCollection
    {
        return RelatedCollection::make(static::getRelated());
    }

    /**
     * @return array
     */
    public static function getMatchByFields()
    {
        return empty(static::$match)
            ? [static::newModel()->getKeyName()]
            : static::$match;
    }

    /**
     * @return array
     * @deprecated
     */
    public static function getOrderByFields()
    {
        return static::sorts();
    }

    public static function sorts(): array
    {
        return empty(static::$sort)
            ? [static::newModel()->getQualifiedKeyName()]
            : static::$sort;
    }

    public static function collectSorts(RestifyRequest $request, Repository $repository): SortCollection
    {
        return SortCollection::make(explode(',', $request->input('sort', '')))
            ->normalize()
            ->allowed($request, $repository)
            ->hydrateDefinition($repository)
            ->hydrateRepository($repository);
    }

    public static function collectFilters($type): Collection
    {
        $filters = collect([
            SearchableFilter::uriKey() => static::getSearchableFields(),
            MatchFilter::uriKey() => static::getMatchByFields(),
            SortableFilter::uriKey() => static::getOrderByFields(),
        ])->get($type);

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
                ? tap($type, fn ($filter) => $filter->column = $filter->column ?? $column)
                : tap(new $base, function ($filter) use ($column, $type) {
                    $filter->type = $type;
                    $filter->column = $column;
                });
        })->values();
    }
}
