<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Eager\Related;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Fields\BelongsToMany;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Fields\FieldCollection;
use Binaryk\LaravelRestify\Getters\Getter;
use Binaryk\LaravelRestify\Http\Controllers\RestResponse;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Models\Concerns\HasActionLogs;
use Binaryk\LaravelRestify\Models\CreationAware;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithAttachers;
use Binaryk\LaravelRestify\Repositories\Concerns\InteractsWithModel;
use Binaryk\LaravelRestify\Repositories\Concerns\Mockable;
use Binaryk\LaravelRestify\Repositories\Concerns\Testing;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Binaryk\LaravelRestify\Traits\HasColumns;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Traits\PerformsQueries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonSerializable;
use ReturnTypeWillChange;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property string $type
 */
class Repository implements RestifySearchable, JsonSerializable
{
    use InteractWithSearch;
    use ValidatingTrait;
    use PerformsQueries;
    use ConditionallyLoadsAttributes;
    use DelegatesToResource;
    use ResolvesActions;
    use ResolvesGetters;
    use RepositoryEvents;
    use WithRoutePrefix;
    use InteractWithFields;
    use InteractsWithModel;
    use InteractsWithAttachers;
    use HasColumns;
    use Mockable;
    use Testing;

    /**
     * This is named `resource` because of the forwarding properties from DelegatesToResource trait.
     */
    public Model $resource;

    /**
     * The list of relations available for the show or index.
     *
     * e.g. ?related=users
     */
    public static array $related;

    /**
     * The relationships that should be eager loaded when performing an index query.
     */
    public static array $with = [];

    /**
     * The list of searchable fields.
     */
    public static array $search;

    /**
     * The list of matchable fields.
     */
    public static array $match;

    /**
     * The list of fields to be sortable.
     */
    public static array $sort;

    /**
     * Attribute that should be used for displaying single model.
     */
    public static string $title = 'id';

    /**
     * Attribute that should be used for displaying the `id` in the json:format.
     */
    public static string $id = 'id';

    /**
     * Indicates if the repository should be globally searchable.
     */
    public static bool $globallySearchable = true;

    /**
     * The number of results to display in the global search.
     */
    public static int $globalSearchResults = 5;

    /**
     * The number of results to display when searching the resource using Scout.
     */
    public static int $scoutSearchResults = 200;

    /**
     * The list of middlewares for the current repository.
     */
    public static array $middleware = [];

    /**
     * The list of attach callable's.
     */
    public static array $attachers = [];

    /**
     * The list of detach callable's.
     */
    public static array $detachers = [];

    /**
     * Specify whether the repository could be accessed public.
     */
    public static bool|array $public = false;

    /**
     * Indicates if the repository is serializing for an eager relationship.
     *
     * The $eagerState will reference to the parent that renders this via related.
     */
    private null|string $eagerState = null;

    /**
     * Extra fields attached to the repository. Useful when display pivot fields.
     */
    public array $extraFields = [];

    /**
     * A collection of pivots for the nested relationships.
     */
    private PivotsCollection $pivots;

    private ?Repository $parentRepository = null;

    public function __construct()
    {
        $this->bootIfNotBooted();
    }

    /**
     * Get the URI key for the repository.
     */
    public static function uriKey(): string
    {
        if (property_exists(static::class, 'uriKey') && is_string(static::$uriKey)) {
            return static::$uriKey;
        }

        $kebabWithoutRepository = Str::kebab(Str::replaceLast('Repository', '', class_basename(get_called_class())));

        /**
         * e.g. UserRepository => users
         * e.g. LaravelEntityRepository => laravel-entities.
         */
        return Str::plural($kebabWithoutRepository);
    }

    /**
     * Get the label for the resource.
     *
     * @return string
     */
    public static function label()
    {
        if (property_exists(static::class, 'label') && is_string(static::$label)) {
            return static::$label;
        }

        $title = Str::title(Str::replaceLast('Repository', '', class_basename(get_called_class())));

        return Str::plural($title);
    }

    /**
     * Get the value that should be displayed to represent the repository.
     */
    public function title(): string
    {
        return $this->{static::$title};
    }

    /**
     * Get the search result subtitle for the repository.
     */
    public function subtitle(): ?string
    {
        return null;
    }

    public function filters(RestifyRequest $request): array
    {
        return [];
    }

    public function collectFields(RestifyRequest $request): FieldCollection
    {
        $method = 'fields';

        if ($request->isIndexRequest() && method_exists($this, 'fieldsForIndex')) {
            $method = 'fieldsForIndex';
        }

        if ($request->isShowRequest() && method_exists($this, 'fieldsForShow')) {
            $method = 'fieldsForShow';
        }

        if ($request->isUpdateRequest() && method_exists($this, 'fieldsForUpdate')) {
            $method = 'fieldsForUpdate';
        }

        if ($request->isStoreRequest() && method_exists($this, 'fieldsForStore')) {
            $method = 'fieldsForStore';
        }

        if ($request->isStoreBulkRequest() && method_exists($this, 'fieldsForStoreBulk')) {
            $method = 'fieldsForStoreBulk';
        }

        if ($request->isUpdateBulkRequest() && method_exists($this, 'fieldsForUpdateBulk')) {
            $method = 'fieldsForUpdateBulk';
        }

        return FieldCollection::make(
            array_values($this->filter($this->{$method}($request)))
        )->merge(
            $this->extraFields($request)
        )->setRepository($this);
    }

    public function extraFields(RestifyRequest $request): array
    {
        return $this->extraFields;
    }

    public function withExtraFields(array $fields): self
    {
        $this->extraFields = $fields;

        return $this;
    }

    public function withPivots(PivotsCollection $pivots): self
    {
        $this->pivots = $pivots;

        return $this;
    }

    public function getPivots(): ?PivotsCollection
    {
        return isset($this->pivots)
            ? $this->pivots
            : null;
    }

    public function withResource($resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Resolve repository with given model.
     */
    public static function resolveWith(Model $model): Repository
    {
        if (static::isMock()) {
            return clone static::getMock()?->withResource($model);
        }

        return resolve(static::class)->withResource($model);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return app(static::class)->$method(...$parameters);
    }

    /**
     * Forward calls to the model (getKey() for example).
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->model(), $method, $parameters);
    }

    /**
     * Defining custom routes.
     *
     * The prefix of this route is the uriKey (e.g. 'restify-api/orders'),
     * The namespace is Http/Controllers
     * Middlewares are the same from config('restify.middleware').
     *
     * However all options could be customized by passing an $options argument
     *
     * @param  bool  $wrap  Choose the routes defined in the @routes method, should be wrapped in a group with attributes by default.
     * If true then all routes will be grouped in a configuration attributes passed by restify, otherwise
     * you should take care of that, by adding $router->group($attributes) in the @routes method
     */
    public static function routes(Router $router, array $attributes, $wrap = true)
    {
        $router->group($attributes, function () {
            // override for custom routes
        });
    }

    /**
     * Return the attributes list.
     *
     * Resolve all model fields through showCallback methods and exclude from the final response if
     * that is required by method
     *
     * @return array
     */
    public function resolveShowAttributes(RestifyRequest $request)
    {
        $fields = $this->collectFields($request)
            ->forShow($request, $this)
            ->filter(fn (Field $field) => $field->authorize($request))
            ->each(fn (Field $field) => $field->resolveForShow($this))
            ->map(fn (Field $field) => $field->serializeToValue($request))
            ->mapWithKeys(fn ($value) => $value)
            ->all();

        if ($this instanceof Mergeable) {
            // Hidden and authorized index fields
            $fields = $this->modelAttributes($request)
                ->filter(function ($value, $attribute) use ($request) {
                    /** * @var Field $field */
                    $field = $this->collectFields($request)->firstWhere('attribute', $attribute);

                    if (is_null($field)) {
                        return true;
                    }

                    if ($field->isHiddenOnShow($request, $this)) {
                        return false;
                    }

                    if (! $field->authorize($request)) {
                        return false;
                    }

                    return true;
                })->all();
        }

        return $fields;
    }

    /**
     * Return the attributes list.
     *
     * @param  RestifyRequest  $request
     * @return array
     */
    public function resolveIndexAttributes($request)
    {
        if ($this instanceof Mergeable) {
            // Hidden and authorized index fields
            return $this->modelAttributes($request)
                ->filter(function ($value, $attribute) use ($request) {
                    /** @var Field $field */
                    $field = $this->collectFields($request)->firstWhere('attribute', $attribute);

                    if (is_null($field)) {
                        return true;
                    }

                    if ($field->isHiddenOnIndex($request, $this)) {
                        return false;
                    }

                    if (! $field->authorize($request)) {
                        return false;
                    }

                    return true;
                })->all();
        }

        // Resolve the show method, and attach the value to the array
        return $this
            ->collectFields($request)
            ->when(
                $this->hasCustomColumns(),
                fn (FieldCollection $fields) => $fields->inList($this->getColumns())
            )
            ->forIndex($request, $this)
            ->filter(fn (Field $field) => $field->authorize($request))
            ->when(
                $this->eagerState,
                fn ($items) => $items->filter(fn (Field $field) => ! $field instanceof EagerField)
            )
            ->each(fn (Field $field) => $field->resolveForIndex($this))
            ->map(fn (Field $field) => $field->serializeToValue($request))
            ->mapWithKeys(fn ($value) => $value)
            ->all();
    }

    public function resolveShowMeta($request)
    {
        if ($request->boolean('withMeta')) {
            return $this->policyMeta($request);
        }

        if (! config('restify.repositories.serialize_show_meta')) {
            return null;
        }

        return $this->policyMeta($request);
    }

    protected function policyMeta(Request $request): array
    {
        return [
            'authorizedToShow' => $this->authorizedToShow($request),
            'authorizedToStore' => static::authorizedToStore($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ];
    }

    public function resolveShowPivots(RestifyRequest $request): array
    {
        if (is_null($pivots = $this->getPivots())) {
            return [];
        }

        return $pivots
            ->filter(fn (Field $field) => $field->authorize($request))
            ->each(fn (Field $field) => $field->resolve($this))
            ->map(fn (Field $field) => $field->serializeToValue($request))
            ->mapWithKeys(fn ($value) => $value)
            ->all();
    }

    public function resolveIndexPivots(RestifyRequest $request): array
    {
        return $this->resolveShowPivots($request);
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @param  RestifyRequest  $request
     */
    public function resolveRelationships($request): array
    {
        if (! $request->related()->hasRelated()) {
            return [];
        }

        return static::collectRelated()
            ->forRequest($request, $this)
            ->mapIntoRelated($request, $this)
            ->unserialized($request, $this)
            ->map(fn (Related $related) => $related->resolve($request, $this)->getValue())
            ->map(function (mixed $items) {
                if ($items instanceof Collection) {
                    return $items->filter()->values();
                }

                return $items;
            })
            ->all();
    }

    /**
     * Returns the format of the metadata for individual item in the index response.
     *
     * @return array
     */
    public function resolveIndexMeta($request)
    {
        if ($request->boolean('withMeta')) {
            return $this->policyMeta($request);
        }

        if (! config('restify.repositories.serialize_index_meta')) {
            return null;
        }

        return $this->policyMeta($request);
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @return array
     */
    public function resolveIndexRelationships($request)
    {
        return $this->resolveRelationships($request);
    }

    public function index(RestifyRequest $request)
    {
        // Check if the user has the policy allowRestify

        // Check if the model was set under the repository
        throw_if(
            $this->model() instanceof NullModel,
            InstanceOfException::because(__('Model is not defined in the repository.'))
        );

        /** *
         * Apply search, match, sort, related.
         *
         * @var LengthAwarePaginator $paginator
         */
        $paginator = RepositorySearchService::make()->search($request, $this)
            ->paginate($request->pagination()->perPage ?? static::$defaultPerPage, page: $request->pagination()->page);

        $items = $this->indexCollection($request, $paginator->getCollection())->map(function ($value) {
            return static::resolveWith($value);
        })->filter(function (self $repository) use ($request) {
            return $repository->authorizedToShow($request);
        })->values();

        $data = $items->map(fn (self $repository) => $repository->serializeForIndex($request));

        return response()->json($this->filter([
            'meta' => $this->when(
                $meta = $this->resolveIndexMainMeta(
                    $request,
                    $models = $items->map(fn (self $repository) => $repository->resource),
                    [
                        'current_page' => $paginator->currentPage(),
                        'from' => $paginator->firstItem(),
                        'last_page' => $paginator->lastPage(),
                        'path' => $paginator->path(),
                        'per_page' => $paginator->perPage(),
                        'to' => $paginator->lastItem(),
                        'total' => $paginator->total(),
                    ]
                ),
                $meta
            ),
            'links' => $this->when(
                $links = $this->resolveIndexLinks($request, $models, [
                    'first' => $paginator->url(1),
                    'next' => $paginator->nextPageUrl(),
                    'path' => $paginator->path(),
                    'prev' => $paginator->previousPageUrl(),
                    'filters' => Restify::path(static::uriKey().'/filters'),
                ]),
                $links
            ),
            'data' => $data,
        ]));
    }

    public function indexCollection(RestifyRequest $request, Collection $items): Collection
    {
        return $items;
    }

    public function resolveIndexMainMeta(RestifyRequest $request, Collection $items, array $paginationMeta): ?array
    {
        return $paginationMeta;
    }

    public function resolveIndexLinks(RestifyRequest $request, Collection $items, array $links): ?array
    {
        return $links;
    }

    public function show(RestifyRequest $request, $repositoryId)
    {
        return data($this->serializeForShow($request));
    }

    public function store(RestifyRequest $request)
    {
        DB::transaction(function () use ($request) {
            static::fillFields(
                $request,
                $this->resource,
                $fields = $this->collectFields($request)
                    ->forStore($request, $this)
                    ->withoutActions($request, $this)
                    ->authorizedStore($request)
                    ->merge($this->collectFields($request)->forBelongsTo($request))
            );

            if ($request->isViaRepository()) {
                $this->resource = $request->viaQuery()->save($this->resource);
            } else {
                if ($this->resource instanceof CreationAware) {
                    $this->resource = $this->resource->createWithAttributes(
                        $this->resource->toArray()
                    );
                } else {
                    $this->resource->save();
                }
            }

            $fields->each(fn (Field $field) => $field->invokeAfter($request, $this->resource));

            $this
                ->collectFields($request)
                ->forStore($request, $this)
                ->withActions($request, $this)
                ->authorizedStore($request)
                ->each(fn (Field $field) => $field->actionHandler->handle($request, $this->resource));
        });

        if (method_exists(static::class, 'stored')) {
            call_user_func([static::class, 'stored'], $this->resource, $request);
        }

        return data($this->serializeForShow($request), 201, ['Location' => static::uriTo($this->resource)]);
    }

    public function storeBulk(RepositoryStoreBulkRequest $request)
    {
        $entities = DB::transaction(function () use ($request) {
            return $request->collectInput()
                ->map(function (array $input, $row) use ($request) {
                    $this->resource = static::newModel();

                    static::fillBulkFields(
                        $request,
                        $this->resource,
                        $fields = $this->collectFields($request)
                            ->forStoreBulk($request, $this)
                            ->withoutActions($request, $this)
                            ->authorizedUpdateBulk($request),
                        $row
                    );

                    $this->resource->save();

                    $fields->each(fn (Field $field) => $field->invokeAfter($request, $this->resource));

                    $this
                        ->collectFields($request)
                        ->forStoreBulk($request, $this)
                        ->withActions($request, $this, $row)
                        ->authorizedUpdateBulk($request)
                        ->each(fn (Field $field) => $field->actionHandler->handle($request, $this->resource, $row));

                    return $this->resource;
                });
        });

        static::savedBulk($entities, $request);
        static::storedBulk($entities, $request);

        return data($entities);
    }

    public function update(RestifyRequest $request, $repositoryId)
    {
        DB::transaction(function () use ($request) {
            $fields = $this->collectFields($request)
                ->forUpdate($request, $this)
                ->withoutActions($request, $this)
                ->authorizedUpdate($request)
                ->merge($this->collectFields($request)->forBelongsTo($request));

            static::fillFields($request, $this->resource, $fields);

            $this->resource->save();

            return $fields;
        })->each(
            fn (Field $field) => $field->invokeAfter($request, $this->resource)
        );

        $this
            ->collectFields($request)
            ->forUpdate($request, $this)
            ->withActions($request, $this)
            ->authorizedUpdate($request)
            ->each(fn (Field $field) => $field->actionHandler->handle($request, $this->resource));

        return data($this->serializeForShow($request));
    }

    public function patch(RestifyRequest $request, $repositoryId)
    {
        $keys = $request->json()->keys();

        DB::transaction(function () use ($request, $keys) {
            $fields = $this->collectFields($request)
                ->filter(
                    fn (Field $field) => in_array($field->attribute, $keys),
                )
                ->forUpdate($request, $this)
                ->withoutActions($request, $this)
                ->authorizedPatch($request)
                ->merge($this->collectFields($request)->forBelongsTo($request));

            static::fillFields($request, $this->resource, $fields);

            if (in_array(HasActionLogs::class, class_uses_recursive($this->resource))) {
                Restify::actionLog()
                    ->forRepositoryUpdated($this->resource, $request->user())
                    ->save();
            }

            $this->resource->save();

            return $fields;
        })->each(
            fn (Field $field) => $field->invokeAfter($request, $this->resource)
        );

        $this
            ->collectFields($request)
            ->filter(
                fn (Field $field) => in_array($field->attribute, $keys),
            )
            ->forUpdate($request, $this)
            ->withActions($request, $this)
            ->authorizedPatch($request)
            ->each(fn (Field $field) => $field->actionHandler->handle($request, $this->resource));

        return data($this->serializeForShow($request));
    }

    public function updateBulk(RestifyRequest $request, $repositoryId, int $row)
    {
        $fields = $this->collectFields($request)
            ->forUpdateBulk($request, $this)
            ->withoutActions($request, $this)
            ->authorizedUpdateBulk($request);

        static::fillBulkFields($request, $this->resource, $fields, $row);

        $this->resource->save();

        $this
            ->collectFields($request)
            ->forUpdateBulk($request, $this)
            ->withActions($request, $this, $row)
            ->authorizedUpdateBulk($request)
            ->each(fn (Field $field) => $field->actionHandler->handle($request, $this->resource, $row));

        return response()->json();
    }

    public function deleteBulk(RestifyRequest $request, $repositoryId, int $row)
    {
        DB::transaction(function () use ($request) {
            if (in_array(HasActionLogs::class, class_uses_recursive($this->resource))) {
                Restify::actionLog()
                    ->forRepositoryDestroy($this->resource, $request->user())
                    ->save();
            }

            return $this->resource->delete();
        });

        return ok(code: 204);
    }

    public function attach(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        $eagerField = $this->authorizeBelongsToMany($request)->belongsToManyField($request);

        DB::transaction(function () use ($request, $pivots, $eagerField) {
            $fields = $eagerField->collectPivotFields()->filter(fn (
                $pivotField
            ) => $request->has($pivotField->attribute))->values();

            $pivots->map(function ($pivot) use ($request, $fields, $eagerField) {
                $eagerField->validate($request, $pivot);

                static::validatorForAttach($request)->validate();

                static::fillFields($request, $pivot, $fields);

                $eagerField->authorizeToAttach($request);

                return $pivot;
            })->each->save();
        });

        return data($pivots, 201);
    }

    public function sync(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        $eagerField = $this->authorizeBelongsToMany($request)->belongsToManyField($request);

        if (! $eagerField) {
            throw new NotFoundHttpException('Belongs to many field not found.');
        }

        $eagerField->authorizeToSync($request);

        $this->model()->{$eagerField->relation}()->sync($pivots->all());

        return ok();
    }

    public function detach(RestifyRequest $request, $repositoryId, Collection $pivots)
    {
        /** * @var BelongsToMany $eagerField */
        $eagerField = $request->repository()::collectRelated()
            ->forManyToManyRelations($request)
            ->firstWhere('attribute', $request->relatedRepository);

        $deleted = DB::transaction(function () use ($pivots, $eagerField, $request) {
            return $pivots
                ->map(fn ($pivot) => $eagerField->authorizeToDetach($request, $pivot) && $pivot->delete());
        });

        return data($deleted, 204);
    }

    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $status = DB::transaction(function () {
            return $this->resource->delete();
        });

        static::deleted($status, $request);

        return ok(code: 204);
    }

    public function allowToUpdate(RestifyRequest $request, $payload = null): self
    {
        $this->authorizeToUpdate($request);

        $validator = static::validatorForUpdate($request, $this, $payload);

        $validator->validate();

        return $this;
    }

    public function allowToPatch(RestifyRequest $request, $payload = null): self
    {
        $this->authorizeToUpdate($request);

        $validator = static::validatorForPatch($request, $this, $payload);

        $validator->validate();

        return $this;
    }

    public function allowToAttach(RestifyRequest $request, Collection $attachers): self
    {
        $methodGuesser = 'attach'.Str::studly($request->relatedRepository);

        $attachers->each(fn ($model) => $this->authorizeToAttach($request, $methodGuesser, $model));

        return $this;
    }

    public function allowToSync(RestifyRequest $request, Collection $attachers): self
    {
        $methodGuesser = 'sync'.Str::studly($request->relatedRepository);

        $this->authorizeToSync($request, $methodGuesser, $attachers);

        return $this;
    }

    public function allowToDetach(RestifyRequest $request, Collection $attachers): self
    {
        $methodGuesser = 'detach'.Str::studly($request->relatedRepository);

        $attachers->each(fn ($model) => $this->authorizeToDetach($request, $methodGuesser, $model));

        return $this;
    }

    public function allowToUpdateBulk(RestifyRequest $request, $payload = null): self
    {
        $this->authorizeToUpdateBulk($request);

        $validator = static::validatorForUpdateBulk($request, $this, $payload);

        $validator->validate();

        return $this;
    }

    public function allowToDestroyBulk(RestifyRequest $request, $payload = null): self
    {
        $this->authorizeToDeleteBulk($request);

        return $this;
    }

    public function allowToStore(RestifyRequest $request, $payload = null): self
    {
        static::authorizeToStore($request);

        $validator = static::validatorForStoring($request, $payload);

        $validator->validate();

        return $this;
    }

    public function allowToBulkStore(RestifyRequest $request, $payload = null, $row = null): self
    {
        static::authorizeToStoreBulk($request);

        $validator = static::validatorForStoringBulk($request, $payload, $row);

        $validator->validate();

        return $this;
    }

    public function allowToDestroy(RestifyRequest $request)
    {
        $this->authorizeToDelete($request);

        return $this;
    }

    /**
     * @return $this
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowToShow($request): self
    {
        $this->authorizeToShow($request);

        return $this;
    }

    public static function storedBulk(Collection $repositories, $request)
    {
        //
    }

    public static function updatedBulk(Collection $repositories, $request)
    {
        //
    }

    public static function savedBulk(Collection $repositories, $request)
    {
        //
    }

    public static function deletedBulk(Collection $repositories, $request)
    {
        //
    }

    public static function updated($model, $request)
    {
        //
    }

    public static function deleted($status, $request)
    {
        //
    }

    public function response($content = '', $status = 200, array $headers = []): RestResponse
    {
        return new RestResponse($content, $status, $headers);
    }

    public function serializeForShow(RestifyRequest $request): array
    {
        return $this->filter([
            'id' => $this->when(optional($this->resource)?->getKey(), fn () => $this->getId($request)),
            'type' => $this->when($type = $this->getType($request), $type),
            'attributes' => $request->isShowRequest() ? $this->resolveShowAttributes($request) : $this->resolveIndexAttributes($request),
            'relationships' => $this->when(value($related = $this->resolveRelationships($request)), $related),
            'meta' => $this->when(
                value($meta = $request->isShowRequest() ? $this->resolveShowMeta($request) : $this->resolveIndexMeta($request)),
                $meta
            ),
            'pivots' => $this->when(value($pivots = $this->resolveShowPivots($request)), $pivots),
        ]);
    }

    public function serializeForIndex(RestifyRequest $request): array
    {
        $data = $this->filter([
            'id' => $this->when($id = $this->getId($request), $id),
            'type' => $this->when($type = $this->getType($request), $type),
            'attributes' => $this->when((bool) $attrs = $this->resolveIndexAttributes($request), $attrs),
            'relationships' => $this->when(value($related = $this->resolveIndexRelationships($request)), $related),
            'meta' => $this->when(value($meta = $this->resolveIndexMeta($request)), $meta),
            'pivots' => $this->when(value($pivots = $this->resolveIndexPivots($request)), $pivots),
        ]);

        return $data;
    }

    protected function getType(RestifyRequest $request): ?string
    {
        return isset(static::$type) && is_string(static::$type)
            ? static::$type
            : $this->model()->getTable();
    }

    protected function getId(RestifyRequest $request): ?string
    {
        if (! static::$id) {
            return null;
        }

        return collect($this->resource->getHidden())->contains(static::$id)
            ? null
            : $this->resource->getAttribute(static::$id);
    }

    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->serializeForShow(app(RestifyRequest::class));
    }

    private function modelAttributes(Request $request = null): Collection
    {
        return collect(method_exists($this->resource, 'toArray') ? $this->resource->toArray() : []);
    }

    /**
     * Fill each field separately.
     *
     * @return Collection
     */
    protected static function fillFields(RestifyRequest $request, Model $model, Collection $fields)
    {
        return $fields->map(fn (Field $field) => $field->fillAttribute($request, $model));
    }

    protected static function fillBulkFields(
        RestifyRequest $request,
        Model $model,
        Collection $fields,
        int $bulkRow = null
    ) {
        return $fields->map(function (Field $field) use ($request, $model, $bulkRow) {
            return $field->fillAttribute($request, $model, $bulkRow);
        });
    }

    public static function uriTo(Model $model): string
    {
        return Str::replaceFirst('//', '/', Restify::path().'/'.static::uriKey().'/'.$model->getKey());
    }

    public static function collectMiddlewares(RestifyRequest $request): Collection
    {
        return collect(static::$middleware);
    }

    public static function getAttachers(): array
    {
        return static::$attachers;
    }

    public static function getDetachers(): array
    {
        return static::$detachers;
    }

    public function eager(EagerField $field = null): Repository
    {
        if (! $field) {
            $this->eagerState = false;

            return $this;
        }

        $this->eagerState = $field->queryKeyThatRendered();
        $this->columns($field->getColumns());

        return $this;
    }

    public function isEagerState(): bool
    {
        return ! is_null($this->eagerState);
    }

    public function getEagerParent(): string
    {
        return $this->eagerState ?? '';
    }

    public function restifyjsSerialize(RestifyRequest $request): array
    {
        return [
            'uriKey' => static::uriKey(),
            'related' => static::collectFilters('matches'),
            'sort' => static::collectFilters('sortables'),
            'match' => static::collectFilters('matches'),
            'searchables' => static::collectFilters('searchables'),
            'actions' => $this->resolveActions($request)
                ->filter(fn (mixed $action) => $action instanceof Action)
                ->filter(fn (Action $action) => $action->isShownOnIndex(
                    $request,
                    $this
                ))->values(),
            'getters' => $this->resolveGetters($request)
                ->filter(fn (mixed $action) => $action instanceof Getter)
                ->filter(fn (Getter $action) => $action->isShownOnIndex(
                    $request,
                    $this
                ))->values(),
        ];
    }

    public static function usesScout(): bool
    {
        return in_array("Laravel\Scout\Searchable", class_uses_recursive(static::newModel()));
    }

    public static function serializer(): Serializer
    {
        return new Serializer(app(static::class));
    }

    public static function isPublic(): bool
    {
        return static::$public;
    }

    public function withParentRepository(Repository $repository): self
    {
        $this->parentRepository = $repository;

        return $this;
    }

    public function parentRepository(): ?Repository
    {
        return $this->parentRepository;
    }
}
