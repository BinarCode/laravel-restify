<?php

namespace Binaryk\LaravelRestify\Traits;

trait HasColumns
{
    /**
     * Specify the list of columns to be resolved from the database.
     *
     * @var array|string
     */
    public array|string $columns = '*';

    public function columns(array|string $columns = []): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function getColumns(): array|string
    {
        return $this->columns;
    }

    public function hasCustomColumns(): bool
    {
        return $this->columns !== '*';
    }
}
