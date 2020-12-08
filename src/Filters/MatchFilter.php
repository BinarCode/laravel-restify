<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class MatchFilter extends Filter
{
    public $column = 'id';

    public function filter(RestifyRequest $request, $query, $value)
    {
        //@todo improve this
        $query->where($this->column, $value);
    }

    public static function makeFromSimple($column, $type): self
    {
        return tap(new static, function (MatchFilter $filter) use ($column, $type) {
            $filter->type = $type;
            $filter->column = $column;
        });
    }

    public static function makeForRepository(Repository $repository): Collection
    {
        return collect($repository::getMatchByFields())->map(function ($type, $column) {
            return static::makeFromSimple($column, $type);
        })->values();
    }

    public function jsonSerialize()
    {
        return [
            'class' => static::class,
            'type' => $this->getType(),
            'key' => static::uriKey(),
            'column' => $this->column,
        ];
    }
}
