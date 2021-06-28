<?php

namespace Binaryk\LaravelRestify\Events;

use Binaryk\LaravelRestify\Filters\AdvancedFiltersCollection;

class AdvancedFiltersApplied
{
    public function __construct(
        public AdvancedFiltersCollection $advancedFiltersCollection
    ) {
    }
}
