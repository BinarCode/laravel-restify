<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\TimestampFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class CreatedAfterDateFilter extends TimestampFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->whereDate('created_at', '>', $value);
    }

    public function rules(Request $request): array
    {
        return [
            'created_at' => 'required|nullable|date',
        ];
    }
}
