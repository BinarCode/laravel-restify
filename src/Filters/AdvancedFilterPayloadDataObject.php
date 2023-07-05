<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\LaravelData\Data;

class AdvancedFilterPayloadDataObject extends Data
{
    public function __construct(
        public string $key,
        public mixed $value,
    )
    {
    }
}
