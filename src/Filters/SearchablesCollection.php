<?php

namespace Binaryk\LaravelRestify\Filters;

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
}