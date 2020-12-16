<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class SortableFilter extends Filter
{
    public $column = 'id';

    public Repository $repository;

    public static $uriKey = 'sortables';

    public function filter(RestifyRequest $request, $query, $direction)
    {
        //@todo improve this
        $query->orderBy($this->column, $direction);
    }

    public static function makeFromSimple(Repository $repository, $column): self
    {
        return tap(new static, function (SortableFilter $filter) use ($column, $repository) {
            $filter->column = $column;
            $filter->repository = $repository;
        });
    }

    public static function makeForRepository(Repository $repository): Collection
    {
        return collect($repository::getOrderByFields())->map(function ($column) use ($repository) {
            return static::makeFromSimple($repository, $column);
        });
    }

    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'key' => static::uriKey(),
            'column' => $this->column,
            'repository_key' => $this->repository::uriKey(),
        ];
    }
}
