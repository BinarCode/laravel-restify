<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Filters\TimestampFilter;
use Illuminate\Http\Request;

class CreatedAfterDateFilter extends TimestampFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->whereDate('created_at', '>', $value);
    }

    function options(Request $request): array
    {
        return [
            'Created At' => 'created_at',
        ];
    }
}
