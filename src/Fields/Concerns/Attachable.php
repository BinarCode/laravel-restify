<?php

namespace Binaryk\LaravelRestify\Fields\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\PivotsCollection;
use Binaryk\LaravelRestify\Traits\ValidatesRelatedKeyShape;
use Closure;
use DateTime;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use ReflectionMethod;

trait Attachable
{
    use ValidatesRelatedKeyShape;

    private const CANONICAL_SYNC_KEYS_ATTRIBUTE = '_restifyCanonicalSyncKeys';

    /**
     * @var callable|null
     */
    private $canAttachCallback;

    /**
     * @var callable|null
     */
    private $canSyncCallback;

    /**
     * @var callable|null
     */
    private $validationCallback;

    /**
     * @var callable|null
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
        $callback = $this->canSyncCallback ?? $this->canAttachCallback;

        return is_callable($callback)
            ? call_user_func($callback, $request, $pivot)
            : true;
    }

    public function authorizeToAttach(RestifyRequest $request)
    {
        $relatedRepositoryIds = Arr::wrap($request->input($request->relatedRepositoryKey()));

        foreach ($relatedRepositoryIds as $relatedRepositoryId) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$this->relation}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToAttach($request, $pivot)) {
                throw new AuthorizationException;
            }
        }

        return $this;
    }

    public function authorizeToSync(RestifyRequest $request)
    {
        $canonicalKeys = $request->attributes->get(self::CANONICAL_SYNC_KEYS_ATTRIBUTE);

        $relatedRepositoryIds = $canonicalKeys instanceof Collection
            ? $canonicalKeys
            : Arr::wrap($request->input($request->relatedRepositoryKey()));

        foreach ($relatedRepositoryIds as $relatedRepositoryId) {
            $pivot = $this->initializePivot(
                $request,
                $request->findModelOrFail()->{$this->relation}(),
                $relatedRepositoryId
            );

            if (! $this->authorizedToSync($request, $pivot)) {
                throw new AuthorizationException;
            }
        }

        return $this;
    }

    /**
     * @internal
     *
     * @param  Collection<array-key, int|string>  $canonicalKeys
     */
    public function authorizeToSyncCanonicalKeys(RestifyRequest $request, Collection $canonicalKeys): static
    {
        $request->attributes->set(self::CANONICAL_SYNC_KEYS_ATTRIBUTE, $canonicalKeys);

        try {
            $this->authorizeToSync($request);
        } finally {
            $request->attributes->remove(self::CANONICAL_SYNC_KEYS_ATTRIBUTE);
        }

        return $this;
    }

    public function authorizedToDetach(RestifyRequest $request, Pivot $pivot): bool
    {
        return $this->hasCanDetachCallback()
            ? call_user_func($this->canDetachCallback, $request, $pivot)
            : true;
    }

    public function hasCustomDetachAuthorization(): bool
    {
        return $this->hasCanDetachCallback()
            || $this->overridesAttachableMethod('authorizedToDetach')
            || $this->overridesAttachableMethod('authorizeToDetach');
    }

    private function overridesAttachableMethod(string $method): bool
    {
        return (new ReflectionMethod($this, $method))->getFileName() !== __FILE__;
    }

    /**
     * @phpstan-assert-if-true callable $this->canDetachCallback
     */
    public function hasCanDetachCallback(): bool
    {
        return is_callable($this->canDetachCallback);
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

        $relatedModel = $request->relatedModelFor(
            $relatedKey,
            applyGlobalScopes: $relatedKeyName !== $relationship->getRelated()->getKeyName(),
        );

        $relatedKey = $relatedModel->getRawOriginal($relatedKeyName);

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
