<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\PivotsCollection;
use Closure;
use DateTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

trait Attachable
{
    /**
     * @var Closure
     */
    private $canAttachCallback;

    /**
     * @var Closure
     */
    private $canSyncCallback;

    /**
     * @var Closure
     */
    private $validationCallback;

    /**
     * @var Closure
     */
    private $canDetachCallback;

    /**
     * The pivot table columns to retrieve.
     *
     * @var array
     */
    public $pivotFields = [];

    public function canAttach(callable|Closure $callback)
    {
        $this->canAttachCallback = $callback;

        return $this;
    }

    public function canSync(callable|Closure $callback)
    {
        $this->canSyncCallback = $callback;

        return $this;
    }

    /**
     * @param  Closure  $callback
     * @return $this
     */
    public function canDetach(callable|Closure $callback)
    {
        $this->canDetachCallback = $callback;

        return $this;
    }

    public function authorizedToAttach(RestifyRequest $request, Pivot $pivot): bool
    {
        return is_callable($this->canAttachCallback)
            ? call_user_func($this->canAttachCallback, $request, $pivot)
            : true;
    }

    public function authorizedToSync(RestifyRequest $request, Pivot $pivot): bool
    {
        return is_callable($this->canAttachCallback)
            ? call_user_func($this->canAttachCallback, $request, $pivot)
            : true;
    }

    public function authorizeToAttach(RestifyRequest $request)
    {
        collect(Arr::wrap($request->input($request->relatedRepository)))->each(function ($relatedRepositoryId) use ($request) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$request->viaRelationship ?? $request->relatedRepository}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToAttach($request, $pivot)) {
                throw new AuthorizationException();
            }
        });

        return $this;
    }

    public function authorizeToSync(RestifyRequest $request)
    {
        collect(Arr::wrap($request->input($request->relatedRepository)))->each(function ($relatedRepositoryId) use ($request) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$request->viaRelationship ?? $request->relatedRepository}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToSync($request, $pivot)) {
                throw new AuthorizationException();
            }
        });

        return $this;
    }

    public function authorizedToDetach(RestifyRequest $request, Pivot $pivot): bool
    {
        return is_callable($this->canDetachCallback)
            ? call_user_func($this->canDetachCallback, $request, $pivot)
            : true;
    }

    public function authorizeToDetach(RestifyRequest $request, Pivot $pivot)
    {
        if (! $this->authorizedToDetach($request, $pivot)) {
            throw new AuthorizationException();
        }

        return $this;
    }

    public function initializePivot(RestifyRequest $request, $relationship, $relatedKey)
    {
        $parentKey = $request->repositoryId;

        $parentKeyName = $relationship->getParentKeyName();
        $relatedKeyName = $relationship->getRelatedKeyName();

        if ($parentKeyName !== $request->model()->getKeyName()) {
            $parentKey = $request->findModelOrFail()->{$parentKeyName};
        }

        if ($relatedKeyName !== ($request->repository($request->route('relatedRepository'))::newModel())->getKeyName()) {
            $relatedModel = $request->repository($request->route('relatedRepository'))::newModel()
                ->newQuery()
                ->whereKey(request('relatedRepositoryId'))
                ->firstOrFail();

            $relatedKey = $relatedModel->{$relatedKeyName};
        }

        ($pivot = $relationship->newPivot())->forceFill([
            $relationship->getForeignPivotKeyName() => $parentKey,
            $relationship->getRelatedPivotKeyName() => $relatedKey,
        ]);

        if ($relationship->withTimestamps) {
            $pivot->forceFill([
                $relationship->createdAt() => new DateTime(),
                $relationship->updatedAt() => new DateTime(),
            ]);
        }

        $fields = $this->collectPivotFields()->values();

        $repository = $request->repository();

        $repository::fillFields($request, $pivot, $fields);

        return $pivot;
    }

    /**
     * Set the columns on the pivot table to retrieve.
     *
     * @param  array|mixed  $fields
     * @return $this
     */
    public function withPivot($fields)
    {
        $this->pivotFields = array_merge(
            $this->pivotFields,
            is_array($fields) ? $fields : func_get_args()
        );

        return $this;
    }

    public function collectPivotFields(): PivotsCollection
    {
        return PivotsCollection::make($this->pivotFields);
    }

    public function validationCallback(Closure $validationCallback)
    {
        $this->validationCallback = $validationCallback;

        return $this;
    }

    public function validate(RestifyRequest $request, $pivot): bool
    {
        if (is_callable($this->validationCallback)) {
            throw_unless(
                call_user_func($this->validationCallback, $request, $pivot),
                ValidationException::withMessages([__('Invalid data.')])
            );
        }

        return true;
    }

    public function unique(): self
    {
        $this->validationCallback = function (RestifyRequest $request, $pivot) {
            $valid = $this->getRelation($request->repository())
                ->where($pivot->toArray())
                ->count() === 0;

            throw_unless($valid, ValidationException::withMessages([__('Invalid data. The relation must be unique.')]));

            return $valid;
        };

        return $this;
    }
}
