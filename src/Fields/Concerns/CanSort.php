<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Illuminate\Support\Str;

trait CanSort
{
    protected ?string $sortableColumn = null;

    public function sortable(string $column): self
    {
        $this->sortableColumn = $column;

        return $this;
    }

    public function isSortable(): bool
    {
        return ! is_null($this->sortableColumn);
    }

    public function qualifySortable(): ?string
    {
        if (! $this->isSortable()) {
            return null;
        }

        if (Str::contains($this->sortableColumn, '.attributes')) {
            return $this->sortableColumn;
        }

        $table = $this->repositoryClass::newModel()->getTable();

        if (Str::contains($this->sortableColumn, '.') && Str::startsWith($this->sortableColumn, $table)) {
            return $table.'.attributes.'.Str::after($this->sortableColumn, "$table.");
        }

        return $table.'.attributes.'.$this->sortableColumn;
    }
}
