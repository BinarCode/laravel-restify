<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\PaginationDataObject;
use Binaryk\LaravelRestify\Filters\RelatedDto;
use Binaryk\LaravelRestify\Http\Requests\Concerns\DetermineRequestType;
use Binaryk\LaravelRestify\Http\Requests\Concerns\InteractWithRepositories;
use Binaryk\LaravelRestify\Http\Requests\Concerns\ResolvesRelatedModels;
use Binaryk\LaravelRestify\Repositories\Repository;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Throwable;

class RestifyRequest extends FormRequest
{
    use DetermineRequestType;
    use InteractWithRepositories;
    use ResolvesRelatedModels;

    public function rules(): array
    {
        return [];
    }

    /**
     * Run the callback with the body narrowed to one bulk row, still keyed by its index, so the
     * row's fields, model events and actions read that row's input only. The query string is kept
     * and the whole body is restored afterwards, even when the callback throws.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function forBulkRow(int $row, Closure $callback): mixed
    {
        $json = $this->json;
        $body = $this->request;
        $files = $this->files;
        $convertedFiles = $this->convertedFiles;

        $key = (string) $row;
        $input = $this->isJson() ? $this->json() : $body;

        $this->convertedFiles = array_key_exists($row, $all = $this->allFiles()) ? [$row => $all[$row]] : [];
        $this->files = new FileBag;
        $this->request = new InputBag;

        if ($files->has($key)) {
            $this->files->set($key, $files->all($key));
        }

        if ($input->has($key)) {
            $this->request->set($key, $input->all($key));
        }

        if ($this->isJson()) {
            $this->json = $this->request;
        }

        try {
            return $callback();
        } finally {
            $this->json = $json;
            $this->request = $body;
            $this->files = $files;
            $this->convertedFiles = $convertedFiles;
        }
    }

    /**
     * The request body, without the query string or uploaded files merged in.
     *
     * @return array<array-key, mixed>
     */
    public function payload(): array
    {
        if ($this->isJson()) {
            return $this->json()->all();
        }

        return array_replace_recursive($this->post(), $this->allFiles());
    }

    /**
     * The `relatedRepository` URL segment (e.g. `users` in `attach/users`).
     *
     * Reads the route parameter directly, never the magic `__get`/`input()`
     * resolution, so a body key of the same name can't shadow it.
     *
     * @throws InvalidArgumentException if the route parameter is not a string.
     */
    public function relatedRepositoryKey(): string
    {
        $relatedRepository = $this->route('relatedRepository');

        if (! is_string($relatedRepository)) {
            throw new InvalidArgumentException('The [relatedRepository] route parameter must be a string.');
        }

        return $relatedRepository;
    }

    /**
     * The `repositoryId` URL segment.
     *
     * Reads the route parameter directly, never the magic `__get`/`input()`
     * resolution, so a body key of the same name can't shadow it.
     *
     * @throws InvalidArgumentException if the route parameter is not a string.
     */
    public function repositoryIdFromRoute(): string
    {
        $repositoryId = $this->route('repositoryId');

        if (! is_string($repositoryId)) {
            throw new InvalidArgumentException('The [repositoryId] route parameter must be a string.');
        }

        return $repositoryId;
    }

    public function relatedEagerField(): EagerField
    {
        $parentRepository = $this->relatedParentRepository();

        /** * @var EagerField $eagerField */
        $eagerField = $parentRepository::collectRelated()
            ->forEager($this)
            ->first(fn (EagerField $field, $key) => $field->getAttribute() === $this->route('repository'));

        if (is_null($eagerField)) {
            abort(JsonResponse::HTTP_FORBIDDEN, 'Eager field missing from the parent ['.$this->route('parentRepository').'] related fields.');
        }

        $eagerField->setParentRepository($parentRepository);

        return $eagerField;
    }

    /**
     * The parent of a nested route, found by its route key and scoped by its repository mainQuery (writes honour
     * `apply_main_query_to_writes`), shown only when its policy allows `show`, and changed through a child only when
     * it also allows `update`.
     *
     * @throws AuthorizationException
     */
    public function relatedParentModel(): Model
    {
        return $this->relatedParentRepository()->resource;
    }

    /**
     * @throws AuthorizationException
     */
    private function relatedParentRepository(): Repository
    {
        return once(function (): Repository {
            $uriKey = $this->route('parentRepository');
            $parentRepository = $this->repository($uriKey);
            $model = $parentRepository::newModel();

            $query = $model->newQuery()->where($model->getRouteKeyName(), $this->route('parentRepositoryId'));

            $query = $this->isMethodSafe()
                ? $parentRepository::mainQuery($this, $query)
                : $this->scopeForWrite($query, $uriKey);

            $parentRepository->withResource($query->firstOrFail());

            $parentRepository->authorizeToShow($this);

            if (! $this->isMethodSafe()) {
                $parentRepository->authorizeToUpdate($this);
            }

            return $parentRepository;
        });
    }

    /**
     * Put the parent key (and morph type) of a nested store into the input, so the child's rules and policy see the
     * parent it is created on. Only has-one and has-many relations create through the parent; a many-to-many child
     * goes through attach.
     *
     * @throws ValidationException when the payload names another parent.
     */
    public function mergeRelatedParentKeys(): void
    {
        $relation = $this->viaQuery();

        if (! $relation instanceof HasOneOrMany) {
            abort(JsonResponse::HTTP_BAD_REQUEST, 'Creating ['.$this->route('repository').'] through ['.$this->route('parentRepository').'] is only supported for has-one and has-many relations. Use the attach endpoint for many-to-many relations.');
        }

        if (($relation instanceof HasOne || $relation instanceof MorphOne) && $relation->exists()) {
            abort(JsonResponse::HTTP_CONFLICT, 'The ['.$this->route('parentRepository').'] already has a ['.$this->route('repository').']. Update it instead.');
        }

        $conflicts = [];

        if ($this->filled($foreignKey = $relation->getForeignKeyName()) && ! $this->matchesParentKey($relation, $this->input($foreignKey))) {
            $conflicts[$foreignKey] = "The {$foreignKey} must match the parent in the URL.";
        }

        if ($relation instanceof MorphOneOrMany && $this->filled($morphType = $relation->getMorphType()) && ! $this->matchesMorphClass($relation, $this->input($morphType))) {
            $conflicts[$morphType] = "The {$morphType} must match the parent in the URL.";
        }

        if ($conflicts !== []) {
            throw ValidationException::withMessages($conflicts);
        }

        $this->merge([$foreignKey => $relation->getParentKey()]);

        if ($relation instanceof MorphOneOrMany) {
            $this->merge([$relation->getMorphType() => $relation->getMorphClass()]);
        }
    }

    /**
     * An integer parent key matches however the payload spells it ("05" for 5), as related ids do.
     *
     * @param  HasOneOrMany<Model, Model, mixed>  $relation
     */
    private function matchesParentKey(HasOneOrMany $relation, mixed $input): bool
    {
        $parentKey = $relation->getParentKey();

        if (! is_int($input) && ! is_string($input) || ! is_int($parentKey) && ! is_string($parentKey)) {
            return false;
        }

        if ($this->hasIntegerKey($relation->getParent()) && $relation->getLocalKeyName() === $relation->getParent()->getKeyName()) {
            return $this->canonicalIntegerKey($input) === $this->canonicalIntegerKey($parentKey);
        }

        return (string) $input === (string) $parentKey;
    }

    /**
     * The morph type matches as the morph alias or as the class the alias maps to.
     *
     * @param  MorphOneOrMany<Model, Model, mixed>  $relation
     */
    private function matchesMorphClass(MorphOneOrMany $relation, mixed $input): bool
    {
        if (! is_string($input)) {
            return false;
        }

        $morphClass = $relation->getMorphClass();

        return (Relation::getMorphedModel($input) ?? $input) === (Relation::getMorphedModel($morphClass) ?? $morphClass);
    }

    public function pagination(): PaginationDataObject
    {
        $perPage = $this->input('page.size') ?? $this->input('perPage');

        $page = is_array($this->input('page')) ? $this->input('page.number') : $this->input('page');

        return PaginationDataObject::fromInput($perPage, $page);
    }

    public function related(): RelatedDto
    {
        try {
            return app(RelatedDto::class)->sync($this, currentRepository() ?? $this->repository());
        } catch (Throwable) {
            return app(RelatedDto::class);
        }
    }

    /**
     * @throws JsonException
     */
    public function filters(): array
    {
        $filters = $this->input('filters');

        if ($this instanceof RepositoryApplyFiltersRequest) {
            return $filters ?? [];
        }

        if ($filters === null) {
            return [];
        }

        return json_decode(base64_decode($filters), true, 512, JSON_THROW_ON_ERROR) ?? [];
    }

    public function groupBy(): ?string
    {
        return $this->input('group_by');
    }
}
