<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use DateTime;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RepositoryAttachController extends RepositoryController
{
    public function __invoke(RepositoryAttachRequest $request)
    {
        $model = $request->findModelOrFail();
        $repository = $request->repository();

        if (is_callable($method = $this->guessMethodName($request, $repository))) {
            return call_user_func($method, $request, $repository, $model);
        }

        return $repository->attach(
            $request, $request->repositoryId,
            collect(Arr::wrap($request->input($request->relatedRepository)))
                ->filter(fn ($relatedRepositoryId) => $request->repository()->allowToAttach($request, $request->attachRelatedModels()))
                ->map(fn ($relatedRepositoryId) => $this->initializePivot(
                    $request, $model->{$request->viaRelationship ?? $request->relatedRepository}(), $relatedRepositoryId
                ))
        );
    }

    /**
     * Initialize a fresh pivot model for the relationship.
     *
     * @param RestifyRequest $request
     * @param $relationship
     * @return mixed
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     * @throws \Binaryk\LaravelRestify\Exceptions\UnauthorizedException
     */
    protected function initializePivot(RestifyRequest $request, $relationship, $relatedKey)
    {
        $parentKey = $request->repositoryId;

        $parentKeyName = $relationship->getParentKeyName();
        $relatedKeyName = $relationship->getRelatedKeyName();

        if ($parentKeyName !== $request->model()->getKeyName()) {
            $parentKey = $request->findModelOrFail()->{$parentKeyName};
        }

        if ($relatedKeyName !== ($request->newRelatedRepository()::newModel())->getKeyName()) {
            $relatedKey = $request->findRelatedModelOrFail()->{$relatedKeyName};
        }

        ($pivot = $relationship->newPivot())->forceFill([
            $relationship->getForeignPivotKeyName() => $parentKey,
            $relationship->getRelatedPivotKeyName() => $relatedKey,
        ]);

        if ($relationship->withTimestamps) {
            $pivot->forceFill([
                $relationship->createdAt() => new DateTime,
                $relationship->updatedAt() => new DateTime,
            ]);
        }

        return $pivot;
    }

    public function guessMethodName(RestifyRequest $request, Repository $repository): ?callable
    {
        $key = $request->relatedRepository;

        if (array_key_exists($key, $repository::getAttachers()) && is_callable($cb = $repository::getAttachers()[$key])) {
            return $cb;
        }

        $methodGuesser = 'attach'.Str::studly($request->relatedRepository);

        if (method_exists($repository, $methodGuesser)) {
            return [$repository, $methodGuesser];
        }

        return null;
    }
}
