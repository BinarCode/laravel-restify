<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class MatchFilter extends Filter
{
    public $column = 'id';

    public Repository $repository;

    public static $uriKey = 'matches';

    public function filter(RestifyRequest $request, $query, $value)
    {
        //@todo improve this
        $query->where($this->column, $value);
    }

    public static function makeFromSimple(Repository $repository, $column, $type): self
    {
        return tap(new static, function (MatchFilter $filter) use ($column, $type, $repository) {
            $filter->type = $type;
            $filter->column = $column;
            $filter->repository = $repository;
        });
    }

    public static function makeForRepository(Repository $repository): Collection
    {
        return collect($repository::getMatchByFields())->map(function ($type, $column) use ($repository) {
            return static::makeFromSimple($repository, $column, $type);
        })->values();
    }

    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'type' => $this->getType(),
            'key' => static::uriKey(),
            'column' => $this->column,
            'repository_key' => $this->repository::uriKey(),
        ];
    }
}
