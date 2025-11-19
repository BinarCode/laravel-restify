<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class ValueFilter extends AdvancedFilter
{
    public ?string $column = 'title';

    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        $operator = $this->rest('operator');
        $column = $this->rest('column');

        $query->where($column, $operator, $value);
    }

    public function rules(Request $request): array
    {
        return [
            'is_active' => 'required|boolean',
        ];
    }
}
