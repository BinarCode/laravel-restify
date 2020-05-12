<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\BooleanFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class ActiveBooleanFilter extends BooleanFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', $value['is_active']);
    }

    public function options(Request $request)
    {
        return [
            'Is Active' => 'is_active',
        ];
    }
}
