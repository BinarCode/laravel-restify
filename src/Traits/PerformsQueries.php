<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait PerformsQueries
{
    public static function query(RestifyRequest $request): Builder|Relation
    {
        return $request->newQuery(static::uriKey());
    }

    public static function indexQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    public static function showQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    public static function mainQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    public static function scoutQuery(RestifyRequest $request, $scoutBuilder)
    {
        return $scoutBuilder;
    }
}
