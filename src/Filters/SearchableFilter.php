<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SearchableFilter extends Filter
{
    public static $uriKey = 'searchables';

    public function filter(RestifyRequest $request, $query, $value)
    {
        //@todo improve this
        $query->where($this->column, 'LIKE', "%{$value}%");
    }
}
