<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class InactiveFilter extends AdvancedFilter
{
    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        $query->where('is_active', false);
    }

    public function rules(Request $request): array
    {
        return [
            'is_active' => 'required|boolean'
        ];
    }
}
