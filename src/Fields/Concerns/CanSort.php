<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait CanSort
{
    protected mixed $sortableColumn = null;

    public function sortable(mixed $column = null): self
    {
        if ($column === false) {
            $this->sortableColumn = null;

            return $this;
        }

        if (is_callable($column)) {
            $this->sortableColumn = $column;

            return $this;
        }

        $this->sortableColumn = is_string($column)
            ? $column
            : $this->getAttribute();

        return $this;
    }

    public function isSortable(RestifyRequest $request = null): bool
    {
        if (is_callable($this->sortableColumn)) {
            $request = $request ?: app(RestifyRequest::class);

            return (bool) call_user_func($this->sortableColumn, $request, $this);
        }

        return is_string($this->sortableColumn);
    }

    public function qualifySortable(RestifyRequest $request): ?string
    {
        return $this->sortableColumn;
    }
}
