<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
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

        $field = $this->belongsToManyField($request);

        if (is_null($field)) {
            abort(JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var list<int|string> $relatedRepositoryIds */
        $relatedRepositoryIds = Arr::wrap($request->input($request->relatedRepositoryKey()));

        /** @var Collection<int, Pivot> $pivots */
        $pivots = Collection::make($relatedRepositoryIds)
            ->map(function (int|string $relatedRepositoryId) use ($request, $model, $field): Pivot {
                /** @var Pivot $pivot */
                $pivot = $field->initializePivot(
                    $request,
                    $model->{$field->relation}(),
                    $relatedRepositoryId
                );

                return $pivot;
            });

        return $repository->attach(
            $request,
            $request->repositoryId,
            $pivots
        );
    }
}
