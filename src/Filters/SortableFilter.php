<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class SortableFilter extends Filter
{
    public $column = 'id';

    public static $uriKey = 'sortables';

    public function filter(RestifyRequest $request, $query, $direction)
    {
        //@todo improve this
        $query->orderBy($this->column, $direction);
    }

    public static function makeFromSimple($column): self
    {
        return tap(new static, function (SortableFilter $filter) use ($column) {
            $filter->column = $column;
        });
    }

    public static function makeForRepository(Repository $repository): Collection
    {
        return collect($repository::getOrderByFields())->map(function ($column) {
            return static::makeFromSimple($column);
        });
    }

    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'key' => static::uriKey(),
            'column' => $this->column,
        ];
    }
}
