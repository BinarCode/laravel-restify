<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Support\Arr;

class RepositoryAttachController extends RepositoryController
{
    use InteractsWithAttachers;

    public function __invoke(RepositoryAttachRequest $request)
    {
        $model = $request->findModelOrFail();
        $repository = $request->repository();

        if (is_callable(
            $method = $this->authorizeBelongsToMany($request)->guessAttachMethod($request)
        )) {
            return call_user_func($method, $request, $repository, $model);
        }

        return $repository->attach(
            $request,
            $request->repositoryId,
            collect(Arr::wrap($request->input($request->relatedRepository)))
                ->filter(fn ($relatedRepositoryId) => $request
                    ->repositoryWith(
                        $request->modelQuery()->firstOrFail()
                    )
                    ->allowToAttach($request, $request->attachRelatedModels()))
                ->map(fn ($relatedRepositoryId) => $this->belongsToManyField($request)
                    ->initializePivot(
                        $request,
                        $model->{$request->viaRelationship ?? $request->relatedRepository}(),
                        $relatedRepositoryId
                    ))
        );
    }
}
