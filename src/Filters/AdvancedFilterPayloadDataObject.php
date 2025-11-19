<?php

namespace Binaryk\LaravelRestify\Filters;

use Spatie\LaravelData\Data;

class AdvancedFilterPayloadDataObject extends Data
{
    public array $rest;

    public function __construct(
        public string $key,
        public mixed $value,
        array $rest = []

    ) {
        $this->rest = $rest;
    }
}
