<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDetachRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RepositoryDetachController extends RepositoryController
{
    use InteractsWithAttachers;

    public function __invoke(RepositoryDetachRequest $request)
    {
        $model = $request->findModelOrFail();
        $repository = $request->repository();

        if (is_callable(
            $method = $this->authorizeBelongsToMany($request)->guessDetachMethod($request)
        )) {
            return call_user_func($method, $request, $repository, $model);
        }

        $repository->allowToDetach($request, $request->detachRelatedModels());

        return $repository->detach(
            $request,
            $request->repositoryId,
            Collection::make(Arr::wrap($request->input($request->relatedRepository)))
                ->map(fn ($relatedRepositoryId) => $this->belongsToManyField($request)
                    ->initializePivot(
                        $request,
                        $model->{$request->viaRelationship ?? $request->relatedRepository}(),
                        $relatedRepositoryId
                    ))
        );
    }
}
