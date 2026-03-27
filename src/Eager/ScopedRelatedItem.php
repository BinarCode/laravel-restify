<?php

namespace Binaryk\LaravelRestify\Eager;

use Binaryk\LaravelRestify\Filters\RelatedDto;
use JsonSerializable;
use ReturnTypeWillChange;

class ScopedRelatedItem implements JsonSerializable
{
    public function __construct(
        private readonly array $data,
    ) {}

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        app(RelatedDto::class)->resolvedRelationships = [];

        return $this->data;
    }
}
