<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class SearchablesCollection extends Collection
{
    public function __construct($items = [])
    {
        $unified = [];

        foreach ($items as $key => $searchable) {
            if ($searchable instanceof SearchableFilter) {
                // Already a SearchableFilter instance - keep it
                $unified[] = $searchable;
            } elseif ($searchable instanceof Filter) {
                // Other Filter instance - keep it
                $unified[] = $searchable;
            } elseif (is_string($searchable)) {
                // String column name (including string callables like "MyClass::method")
                $filter = new SearchableFilter;
                $filter->setColumn($searchable);
                $unified[] = $filter;
            } elseif (is_string($key) && ! is_numeric($key)) {
                // Array key is the field name
                $filter = new SearchableFilter;
                $filter->setColumn($key);
                $unified[] = $filter;
            } elseif (is_callable($searchable)) {
                // Non-string callables (closures, arrays, invokables)
                $filter = new SearchableFilter;
                $filter->usingClosure($searchable);
                $filter->setColumn(is_string($key) && ! is_numeric($key) ? $key : 'unknown');
                $unified[] = $filter;
            }
        }

        parent::__construct($unified);
    }

    /**
     * Get searchable field names only (no filter objects).
     */
    public function fieldNames(): array
    {
        return $this->filter(fn ($item) => is_string($item) && ! empty($item))
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Format searchable fields for documentation.
     */
    public function formatForDocumentation(): string
    {
        $fields = $this->fieldNames();

        if (empty($fields)) {
            return 'No searchable fields available';
        }

        return implode(', ', $fields);
    }

    /**
     * Process searchables for search functionality, creating SearchableFilter instances.
     */
    public function forSearch(Model $model, Repository $repository): Collection
    {
        return $this->map(function ($searchable, $key) use ($model, $repository) {
            // If it's already a Filter instance, set repository and return
            if ($searchable instanceof Filter) {
                return $searchable->setRepository($repository);
            }

            // Create SearchableFilter for string fields
            $columnName = is_numeric($key) ? $searchable : $key;

            return SearchableFilter::make()
                ->setColumn($model->qualifyColumn($columnName))
                ->setRepository($repository);
        });
    }

    /**
     * Apply all searchable filters to the query with the given search value.
     *
     * @param  Builder|Relation  $query
     * @return $this
     */
    public function apply(RestifyRequest $request, $query, string $searchValue): self
    {
        return $this->each(function (SearchableFilter $filter) use ($request, $query, $searchValue) {
            $filter->filter($request, $query, $searchValue);
        });
    }

    /**
     * Set repository for all filters in the collection.
     */
    public function setRepository(Repository $repository): self
    {
        return $this->each(function ($filter) use ($repository) {
            if ($filter instanceof Filter) {
                $filter->setRepository($repository);
            }
        });
    }

    /**
     * Qualify all column names with the model table.
     */
    public function qualifyColumns(Model $model): self
    {
        return $this->each(function ($filter) use ($model) {
            if ($filter instanceof SearchableFilter && $filter->column()) {
                // Only qualify if not already qualified
                if (! str_contains($filter->column(), '.')) {
                    $filter->setColumn($model->qualifyColumn($filter->column()));
                }
            }
        });
    }
}
