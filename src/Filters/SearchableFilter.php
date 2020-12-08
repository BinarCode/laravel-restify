<?php

namespace Binaryk\LaravelRestify\Filters;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Support\Collection;

class SearchableFilter extends Filter
{
    public $column = 'id';

    public static $uriKey = 'searchables';

    public function filter(RestifyRequest $request, $query, $value)
    {
        //@todo improve this
        $query->where($this->column, 'LIKE', "%{$value}%");
    }

    public static function makeFromSimple($column): self
    {
        return tap(new static, function (SearchableFilter $filter) use ($column) {
            $filter->column = $column;
        });
    }

    public static function makeForRepository(Repository $repository): Collection
    {
        return collect($repository::getSearchableFields())->map(function ($column) {
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
