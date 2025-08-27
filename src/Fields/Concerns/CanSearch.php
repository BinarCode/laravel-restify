<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait CanSearch
{
    protected mixed $searchableColumn = null;

    protected ?string $searchableType = null;

    public function searchable(...$attributes): self
    {
        // Handle case where false is passed to disable searchable
        if (count($attributes) === 1 && $attributes[0] === false) {
            $this->searchableColumn = null;
            $this->searchableType = null;

            return $this;
        }

        // Handle case where no attributes are provided
        if (empty($attributes)) {
            $this->searchableColumn = $this->getAttribute();
            $this->searchableType = 'text';

            return $this;
        }

        $firstAttribute = $attributes[0];

        // Handle closures/callables
        if (is_callable($firstAttribute)) {
            $this->searchableColumn = $firstAttribute;
            $this->searchableType = 'custom';

            return $this;
        }

        // Handle SearchableFilter instances
        if ($firstAttribute instanceof SearchableFilter) {
            $this->searchableColumn = $firstAttribute;
            $this->searchableType = 'custom';

            return $this;
        }

        // Handle invokable objects
        if (is_object($firstAttribute) && method_exists($firstAttribute, '__invoke')) {
            $this->searchableColumn = $firstAttribute;
            $this->searchableType = 'custom';

            return $this;
        }

        // Handle string column names
        if (is_string($firstAttribute)) {
            $this->searchableColumn = $firstAttribute;
            // Check if second parameter is a type
            $this->searchableType = (count($attributes) > 1 && is_string($attributes[1])) 
                ? $attributes[1] 
                : 'text';

            return $this;
        }

        // Default case
        $this->searchableColumn = $this->getAttribute();
        $this->searchableType = 'text';

        return $this;
    }

    public function searchableCallback(callable $callback): self
    {
        return $this->searchable($callback);
    }

    public function isSearchable(?RestifyRequest $request = null): bool
    {
        if (is_callable($this->searchableColumn)) {
            return true;
        }

        if (is_object($this->searchableColumn) && method_exists($this->searchableColumn, '__invoke')) {
            return true;
        }

        return ! is_null($this->searchableColumn);
    }

    public function getSearchColumn(?RestifyRequest $request = null): mixed
    {
        if (! $this->isSearchable($request)) {
            return null;
        }

        return $this->searchableColumn;
    }
}
