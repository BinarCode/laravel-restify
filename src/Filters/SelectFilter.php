<?php

namespace Binaryk\LaravelRestify\Filters;

use Illuminate\Http\Request;

abstract class SelectFilter extends AdvancedFilter
{
    public string $type = 'select';

    abstract public function options(Request $request): array;
}
