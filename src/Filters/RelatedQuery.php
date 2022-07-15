<?php

namespace Binaryk\LaravelRestify\Filters;

class RelatedQuery
{
    public RelatedQueryCollection $nested;

    public function __construct(
        public string $relation,
        public bool $loaded = false,
        public array $columns = [],
        RelatedQueryCollection $nested = null,
    ) {
        $this->nested = $nested ?? RelatedQueryCollection::make([]);
    }

    public function columns(): array
    {
        return $this->columns;
    }
}
