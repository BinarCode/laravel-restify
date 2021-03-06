<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class InactiveFilter extends AdvancedFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', false);
    }

    public function options(Request $request): array
    {
        return [
            'Active' => 'is_active',
        ];
    }
}
