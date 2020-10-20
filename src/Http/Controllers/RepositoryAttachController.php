<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\HasMany;
use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use DateTime;
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
            $request, $request->repositoryId,
            collect(Arr::wrap($request->input($request->relatedRepository)))
                ->filter(fn($relatedRepositoryId) => $request->repository()->allowToAttach($request, $request->attachRelatedModels()))
                ->map(fn($relatedRepositoryId) => $this->belongsToManyField($request)
                    ->initializePivot(
                        $request, $model->{$request->viaRelationship ?? $request->relatedRepository}(), $relatedRepositoryId
                    ))
        );
    }

    /**
     * Initialize a fresh pivot model for the relationship.
     *
     * @param RestifyRequest $request
     * @param $relationship
     * @param $relatedKey
     * @param BelongsToMany|HasMany $field
     * @return mixed
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     * @throws \Binaryk\LaravelRestify\Exceptions\UnauthorizedException
     */
}
