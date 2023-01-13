<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\BooleanFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class ActiveBooleanFilter extends BooleanFilter
{
    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        $query->where('is_active', $this->input('is_active'));
    }

    public function rules(Request $request): array
    {
        return [
            'is_active' => 'bool',
        ];
    }
}
