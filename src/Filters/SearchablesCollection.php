<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Model;
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

        foreach ($items as $column => $searchable) {
            if ($searchable instanceof SearchableFilter) {
                // Extract column name from Filter object
                $unified[] = $searchable->column();
            } elseif (is_string($searchable)) {
                // Direct string field name
                $unified[] = $searchable;
            } elseif (is_string($column) && is_numeric($column) === false) {
                // Array key is the field name
                $unified[] = $column;
            } elseif (is_numeric($column) && is_string($searchable)) {
                // Numeric key with string value (e.g., ['name', 'email'])
                $unified[] = $searchable;
            }
        }

        parent::__construct(array_unique($unified));
    }

    /**
     * Get searchable field names only (no filter objects).
     */
    public function fieldNames(): array
    {
        return $this->filter(fn($item) => is_string($item) && !empty($item))
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
}