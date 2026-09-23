<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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

        $request->repositoryWith($model)->allowToAttach($request, $request->attachRelatedModels());

        return $repository->attach(
            $request,
            $request->repositoryId,
            Collection::make(Arr::wrap($request->input($request->relatedRepositoryKey())))
                ->map(fn ($relatedRepositoryId) => $this->belongsToManyField($request)
                    ->initializePivot(
                        $request,
                        $model->{$request->viaRelationship ?? $request->relatedRepositoryKey()}(),
                        $relatedRepositoryId
                    ))
        );
    }
}
