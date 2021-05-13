<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait PerformsQueries
{
    public static function query(RestifyRequest $request): Builder | Relation
    {
        return $request->newQuery(static::uriKey());
    }

    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    public static function detailQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    public static function showQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "show" and "index" query for the given repository.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function mainQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build an "attach" query for the given repository.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function attachQuery(RestifyRequest $request, $query)
    {
        return $query;
    }
}
