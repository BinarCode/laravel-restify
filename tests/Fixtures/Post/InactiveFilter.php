<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class InactiveFilter extends AdvancedFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', false);
    }
}
