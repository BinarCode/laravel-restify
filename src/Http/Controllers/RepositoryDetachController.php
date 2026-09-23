<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDetachRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Http\JsonResponse;
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

        $field = $this->belongsToManyField($request);

        if (is_null($field)) {
            abort(JsonResponse::HTTP_BAD_REQUEST);
        }

        $relatedRepositoryIds = Arr::wrap($request->input($request->relatedRepositoryKey()));

        $pivots = Collection::make($relatedRepositoryIds)
            ->map(fn ($relatedRepositoryId) => $field->initializePivot(
                $request,
                $model->{$field->relation}(),
                $relatedRepositoryId
            ));

        return $repository->detach(
            $request,
            $request->repositoryId,
            $pivots
        );
    }
}
