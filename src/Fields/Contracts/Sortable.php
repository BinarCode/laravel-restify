<?php

namespace Binaryk\LaravelRestify\Fields\Contracts;

interface Sortable
{
    public function sortable(string $column): self;

    public function isSortable(): bool;

    public function qualifySortable(): ?string;
}
