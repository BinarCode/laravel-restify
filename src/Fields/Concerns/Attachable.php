<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\PivotsCollection;
use Binaryk\LaravelRestify\Traits\ValidatesRelatedKeyShape;
use Closure;
use DateTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait Attachable
{
    use ValidatesRelatedKeyShape;

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

    private const RELATED_MODELS_CACHE_ATTRIBUTE = '_restifyAttachableRelatedModels';

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
        collect(Arr::wrap($request->input($request->relatedRepositoryKey())))->each(function ($relatedRepositoryId) use ($request) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$request->viaRelationship ?? $request->relatedRepositoryKey()}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToAttach($request, $pivot)) {
                throw new AuthorizationException;
            }
        });

        return $this;
    }

    public function authorizeToSync(RestifyRequest $request)
    {
        collect(Arr::wrap($request->input($request->relatedRepositoryKey())))->each(function ($relatedRepositoryId) use ($request) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$request->viaRelationship ?? $request->relatedRepositoryKey()}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToSync($request, $pivot)) {
                throw new AuthorizationException;
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
            throw new AuthorizationException;
        }

        return $this;
    }

    public function initializePivot(RestifyRequest $request, $relationship, $relatedKey)
    {
        if (! $relationship instanceof BelongsToMany) {
            throw new InvalidArgumentException('The relationship must be a BelongsToMany (or MorphToMany) relation.');
        }

        $relatedRepository = $request->relatedRepositoryKey();

        $relatedKey = $this->assertValidRelatedKeyShape($relatedKey, $relatedRepository);

        $parentKeyName = $relationship->getParentKeyName();
        $relatedKeyName = $relationship->getRelatedKeyName();

        $parentKey = $request->findModelOrFail()->{$parentKeyName};

        $relatedRepositoryModel = $request->repository($relatedRepository)::newModel();

        if ($relatedKeyName !== $relatedRepositoryModel->getKeyName()) {
            $relatedKey = $this->resolveNonPrimaryRelatedKey($request, $relatedRepositoryModel, $relatedKeyName, $relatedKey);
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

        $fields = $this->collectPivotFields()->values();

        $repository = $request->repository();

        $repository::fillFields($request, $pivot, $fields);

        return $pivot;
    }

    private function resolveNonPrimaryRelatedKey(RestifyRequest $request, Model $relatedRepositoryModel, string $relatedKeyName, int|string $primaryKey): mixed
    {
        $relatedModel = $this->relatedModelsByPrimaryKey($request, $relatedRepositoryModel)->get($primaryKey);

        if (is_null($relatedModel)) {
            throw (new ModelNotFoundException)->setModel($relatedRepositoryModel::class, [$primaryKey]);
        }

        return $relatedModel->{$relatedKeyName};
    }

    /**
     * `initializePivot()` is called once per id from several places (controllers,
     * authorizeToAttach/Sync), so this memoizes the lookup per request instead of
     * re-querying for each one.
     *
     * @return Collection<int|string, Model>
     */
    private function relatedModelsByPrimaryKey(RestifyRequest $request, Model $relatedRepositoryModel): Collection
    {
        $cached = $request->attributes->get(self::RELATED_MODELS_CACHE_ATTRIBUTE);

        if ($cached instanceof Collection) {
            return $cached;
        }

        $relatedRepository = $request->relatedRepositoryKey();

        $primaryKeyName = $relatedRepositoryModel->getKeyName();

        $relatedModels = $relatedRepositoryModel->newQuery()
            ->whereIn($primaryKeyName, Arr::wrap($request->input($relatedRepository)))
            ->get()
            ->keyBy($primaryKeyName);

        $request->attributes->set(self::RELATED_MODELS_CACHE_ATTRIBUTE, $relatedModels);

        return $relatedModels;
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
