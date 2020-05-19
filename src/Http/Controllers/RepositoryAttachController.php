<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryAttachRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use DateTime;
use Illuminate\Support\Arr;

class RepositoryAttachController extends RepositoryController
{
    public function __invoke(RepositoryAttachRequest $request)
    {
        $model = $request->findModelOrFail();
        $repository = $request->repository()->allowToUpdate($request);

        return $repository->attach(
            $request, $request->repositoryId,
            collect(Arr::wrap($request->input($request->relatedRepository)))
            ->map(fn($relatedRepositoryId) => $this->initializePivot(
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

}
