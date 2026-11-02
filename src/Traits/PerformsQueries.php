<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait PerformsQueries
{
    public static function query(RestifyRequest $request): Builder|Relation
    {
        return $request->newQuery(static::uriKey());
    }

    /**
     * Customize the query for index and global search requests.
     *
     * Restrict here the rows a user may not list (mainQuery also works, but scopes show, update and delete too). The show policy is only checked on the
     * paginated rows, so the pagination meta, links and filter results still account for rows it hides.
     */
    public static function indexQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    public static function showQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    /**
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public static function mainQuery(RestifyRequest $request, Builder|Relation $query)
    {
        return $query;
    }

    public static function scoutQuery(RestifyRequest $request, $scoutBuilder)
    {
        return $scoutBuilder;
    }
}
