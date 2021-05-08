<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\BooleanFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ActiveBooleanFilter extends BooleanFilter
{
    public function filter(RestifyRequest $request, Builder $query, $value)
    {
        $query->where('is_active', $this->input('is_active'));
    }

    public function options(Request $request): array
    {
        return [
            'Is Active' => 'is_active',
        ];
    }
}
