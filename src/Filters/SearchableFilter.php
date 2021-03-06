<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class SearchableFilter extends Filter
{
    const TYPE = 'searchable';

    public function filter(RestifyRequest $request, $query, $value)
    {
        $connectionType = $this->repository->model()->getConnection()->getDriverName();

        $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

        $query->orWhere($this->column, $likeOperator, '%'.$value.'%');
    }
}
