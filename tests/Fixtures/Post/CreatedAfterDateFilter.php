<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\TimestampFilter;

class CreatedAfterDateFilter extends TimestampFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->whereDate('created_at', '>', $value);
    }
}
