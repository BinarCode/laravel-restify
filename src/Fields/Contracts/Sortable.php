<?php

namespace Binaryk\LaravelRestify\Fields\Contracts;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

interface Sortable
{
    public function sortable(mixed $column = null): self;

    public function isSortable(RestifyRequest $request): bool;

    public function qualifySortable(RestifyRequest $request): ?string;
}
