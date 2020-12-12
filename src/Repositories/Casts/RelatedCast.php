<?php

namespace Binaryk\LaravelRestify\Repositories\Casts;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class RelatedCast extends RepositoryCast
{
    public static function fromBuilder(RestifyRequest $request, Builder $builder, Repository $repository): Collection
    {
        return $builder->take($request->input('relatablePerPage') ?? ($repository::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();
    }

    public static function fromRelation(RestifyRequest $request, Relation $relation, Repository $repository): Collection
    {
        return $relation->take($request->input('relatablePerPage') ?? ($repository::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();
    }
}
