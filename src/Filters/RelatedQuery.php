<?php

namespace Binaryk\LaravelRestify\Filters;

class RelatedQuery
{
    public function __construct(
        public string $relation,
        public bool $loaded = false,
        public array $columns = [],
        public ?RelatedQueryCollection $nested = null,
    ) {
        $this->nested = RelatedQueryCollection::make([]);
    }
}
