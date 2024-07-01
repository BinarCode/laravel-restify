<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\LaravelData\Data;

class PaginationDataObject extends Data
{
    public function __construct(
        public int|string|null $perPage,
        public int|string|null $page,
    ) {}
}
