<?php

namespace Binaryk\LaravelRestify\Repositories\Casts;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RelatedCast extends RepositoryCast
{
    public static function fromBuilder(Request $request, Builder $builder): Collection
    {
        return $builder->take($request->input('relatablePerPage') ?? (static::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();
    }

    public static function fromRelation(Request $request, Relation $relation): Collection
    {
        return $relation->take($request->input('relatablePerPage') ?? (static::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))->get();
    }
}