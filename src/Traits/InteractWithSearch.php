<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Illuminate\Support\Collection;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
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

    /**
     * @return array
     */
    public static function getWiths()
    {
        return static::$withs ?? [];
    }

    /**
     * @return array
     */
    public static function getRelated()
    {
        return static::$related ?? [];
    }

    public static function gerRelatedKeys(): array
    {
        return collect(static::getRelated())
            ->map(fn ($value, $key) => is_numeric($key) ? $value : $key)
            ->values()
            ->all();
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
     */
    public static function getOrderByFields()
    {
        return empty(static::$sort)
            ? [static::newModel()->getQualifiedKeyName()]
            : static::$sort;
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
