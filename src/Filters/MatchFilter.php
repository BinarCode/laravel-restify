<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class MatchFilter extends Filter
{
    public static $uriKey = 'matches';

    public function filter(RestifyRequest $request, $query, $value)
    {
        //@todo improve this
        $query->where($this->column, $value);
    }
}
