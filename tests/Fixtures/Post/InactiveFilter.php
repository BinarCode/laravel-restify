<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class InactiveFilter extends Filter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', false);
    }
}
