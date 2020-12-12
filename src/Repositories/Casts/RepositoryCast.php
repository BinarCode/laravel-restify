<?php

namespace Binaryk\LaravelRestify\Repositories\Casts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

abstract class RepositoryCast
{
    abstract public static function fromBuilder(Request $request, Builder $builder): Collection;

    abstract public static function fromRelation(Request $request, Relation $relation): Collection;
}
