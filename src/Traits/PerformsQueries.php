<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

trait PerformsQueries
{
    /**
     * Build an "index" query for the given repository.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "show" query for the given repository.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function showQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "relatable" query for the given repository.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  RestifyRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(RestifyRequest $request, $query)
    {
        return $query;
    }
}
