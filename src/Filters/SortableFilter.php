<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SortableFilter extends Filter
{
    public static $uriKey = 'sortables';

    public function filter(RestifyRequest $request, $query, $direction)
    {
        $query->orderBy($this->column,
            $direction === '-'
                ? 'desc'
                : 'asc'
        );
    }
}
