<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Casts\RepositoryCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class RelatedCastWithAttributes extends RepositoryCast
{
    public static function fromBuilder(RestifyRequest $request, Builder $builder): Collection
    {
        return $builder->take($request->input('relatablePerPage') ?? ($request->repository()::$defaultPerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))
            ->get()
            ->map(fn ($item) => ['attributes' => $item->toArray()]);
    }

    public static function fromRelation(RestifyRequest $request, Relation $relation): Collection
    {
        return $relation->take($request->input('relatablePerPage') ?? ($request->repository()::$defaultPerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE))
        ->get()
        ->map(fn ($item) => ['attributes' => $item->toArray()]);
    }
}
