<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Traits\PerformsQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonSerializable;

/**
 * This class serve as repository collection and repository single model
 * This allow you to use all of the Laravel default repositories features (as adding headers in the response, or customizing
 * response).
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class Repository implements RestifySearchable, JsonSerializable
{
    use InteractWithSearch,
        ValidatingTrait,
        RepositoryFillFields,
        PerformsQueries,
        ConditionallyLoadsAttributes,
        DelegatesToResource;

    /**
     * This is named `resource` because of the forwarding properties from DelegatesToResource trait.
     * This may be a single model or a illuminate collection, or even a paginator instance.
     *
     * @var Model|LengthAwarePaginator
     */
    public $resource;

    /**
     * The list of relations available for the details or index.
     *
     * e.g. ?with=users
     * @var array
     */
    public static $related;

    /**
     * The list of searchable fields.
     *
     * @var array
     */
    public static $search;

    /**
     * The list of matchable fields.
     *
     * @var array
     */
    public static $match;

    /**
     * The list of fields to be sortable.
     *
     * @var array
     */
    public static $sort;

    /**
     * Get the underlying model instance for the resource.
     *
     * @return \Illuminate\Database\Eloquent\Model|LengthAwarePaginator
     */
    public function model()
    {
        return $this->resource ?? static::newModel();
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
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
     * Get a fresh instance of the model represented by the resource.
     *
     * @return mixed
     */
    public static function newModel(): Model
    {
        if (property_exists(static::class, 'model')) {
            $model = static::$model;
        } else {
            $model = NullModel::class;
        }

        return new $model;
    }

    public static function query(): Builder
    {
        return static::newModel()->query();
    }

    /**
     * Resolvable attributes before storing/updating.
     *
     * @param RestifyRequest $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [];
    }

    /**
     * @param RestifyRequest $request
     * @return Collection
     */
    public function collectFields(RestifyRequest $request)
    {
        return collect($this->fields($request))->filter(function (Field $field) use ($request) {
            return $field->filter($request);
        });
    }

    private function indexFields(RestifyRequest $request): Collection
    {
        return $this->collectFields($request)
            ->filter(fn(Field $field) => !$field->isHiddenOnIndex($request, $this))
            ->values();
    }

    private function showFields(RestifyRequest $request): Collection
    {
        return $this->collectFields($request)
            ->filter(fn(Field $field) => !$field->isHiddenOnDetail($request, $this))
            ->values();
    }

    /**
     * @param $resource
     * @return Repository
     */
    public function withResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Resolve repository with given model.
     *
     * @param $model
     * @return Repository
     */
    public static function resolveWith($model)
    {
        /** * @var Repository $self */
        $self = resolve(static::class);

        return $self->withResource($model);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Forward calls to the model (getKey() for example).
     * @param $method
     * @param $parameters
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
     * @param Router $router
     * @param array $attributes
     * @param bool $wrap Choose the routes defined in the @routes method, should be wrapped in a group with attributes by default.
     * If true then all routes will be grouped in a configuration attributes passed by restify, otherwise
     * you should take care of that, by adding $router->group($attributes) in the @routes method
     */
    public static function routes(Router $router, $attributes, $wrap = false)
    {
        $router->group($attributes, function ($router) {
            // override for custom routes
        });
    }

    /**
     * Return the attributes list.
     *
     * Resolve all model fields through showCallback methods and exclude from the final response if
     * that is required by method
     *
     * @param $request
     * @return array
     */
    public function resolveDetailsAttributes(RestifyRequest $request)
    {
        $fields = $this->showFields($request)
            ->filter(fn(Field $field) => $field->authorize($request))
            ->each(fn(Field $field) => $field->resolveForShow($this))
            ->map(fn(Field $field) => $field->serializeToValue($request))
            ->mapWithKeys(fn($value) => $value)
            ->all();

        if ($this instanceof Mergeable) {
            // Hiden and authorized index fields
            $fields = $this->modelAttributes($request)
                ->filter(function ($value, $attribute) use ($request) {
                    /** * @var Field $field */
                    $field = $this->collectFields($request)->firstWhere('attribute', $attribute);

                    if (is_null($field)) {
                        return true;
                    }

                    if ($field->isHiddenOnDetail($request, $this)) {
                        return false;
                    }

                    if (!$field->authorize($request)) {
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
     * @param RestifyRequest $request
     * @return array
     */
    public function resolveIndexAttributes($request)
    {
        // Resolve the show method, and attach the value to the array
        $fields = $this->indexFields($request)
            ->filter(fn(Field $field) => $field->authorize($request))
            ->each(fn(Field $field) => $field->resolveForIndex($this))
            ->map(fn(Field $field) => $field->serializeToValue($request))
            ->mapWithKeys(fn($value) => $value)
            ->all();

        if ($this instanceof Mergeable) {
            // Hiden and authorized index fields
            $fields = $this->modelAttributes($request)
                ->filter(function ($value, $attribute) use ($request) {
                    /** * @var Field $field */
                    $field = $this->collectFields($request)->firstWhere('attribute', $attribute);

                    if (is_null($field)) {
                        return true;
                    }

                    if ($field->isHiddenOnIndex($request, $this)) {
                        return false;
                    }

                    if (!$field->authorize($request)) {
                        return false;
                    }

                    return true;
                })->all();
        }

        return $fields;
    }

    /**
     * @param $request
     * @return array
     */
    public function resolveDetailsMeta($request)
    {
        return [
            'authorizedToShow' => $this->authorizedToShow($request),
            'authorizedToStore' => $this->authorizedToStore($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ];
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @param $request
     * @return array
     */
    public function resolveRelationships($request): array
    {
        if (is_null($request->get('related'))) {
            return [];
        }

        $withs = [];

        with(explode(',', $request->get('related')), function ($relations) use ($request, &$withs) {
            foreach ($relations as $relation) {
                if (in_array($relation, static::getRelated())) {
                    // @todo check if the resource has the relation
                    /** * @var AbstractPaginator $paginator */
                    $paginator = $this->resource->{$relation}()->paginate($request->get('relatablePerPage') ?? (static::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE));

                    $withs[$relation] = $paginator->getCollection()->map(fn(Model $item) => [
                        'attributes' => $item->toArray(),
                    ]);
                }
            }
        });

        return $withs;
    }

    /**
     * @param $request
     * @return array
     */
    public function resolveIndexMeta($request)
    {
        return $this->resolveDetailsMeta($request);
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @param $request
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
        throw_if($this->model() instanceof NullModel, InstanceOfException::because(__('Model is not defined in the repository.')));

        /** *
         * Apply all of the query: search, match, sort, related.
         * @var AbstractPaginator $paginator
         */
        $paginator = RepositorySearchService::instance()->search($request, $this)->tap(function ($query) use ($request) {
            // Call the local definition of the query
            static::indexQuery($request, $query);
        })->paginate($request->perPage ?? (static::$defaultPerPage ?? RestifySearchable::DEFAULT_PER_PAGE));

        $items = $paginator->getCollection()->map(function ($value) {
            return static::resolveWith($value);
        })->filter(function (self $repository) use ($request) {
            return $repository->authorizedToShow($request);
        })->values(); //->map(fn (self $repository) => $this->filter($repository->serializeIndex($request)));

        return $this->response([
            'meta' => RepositoryCollection::meta($paginator->toArray()),
            'links' => RepositoryCollection::paginationLinks($paginator->toArray()),
            'data' => $items,
        ]);
    }

    public function show(RestifyRequest $request, $repositoryId)
    {
        try {
            $this->allowToShow($request);
        } catch (AuthorizationException $e) {
            return $this->response()->forbidden()->addError($e->getMessage());
        }

        return $this->response()->data($this->jsonSerialize());
    }

    public function store(RestifyRequest $request)
    {
        try {
            $this->allowToStore($request);
        } catch (AuthorizationException | UnauthorizedException $e) {
            return $this->response()->addError($e->getMessage())->code(RestResponse::REST_RESPONSE_FORBIDDEN_CODE);
        } catch (ValidationException $e) {
            return $this->response()->addError($e->errors())
                ->code(RestResponse::REST_RESPONSE_INVALID_CODE);
        }

        $this->resource = static::storePlain($request->toArray());

        static::stored($this->resource, $request);

        return $this->response('', RestResponse::REST_RESPONSE_CREATED_CODE)
            ->model($this->resource)
            ->header('Location', Restify::path() . '/' . static::uriKey() . '/' . $this->resource->id);
    }

    public function update(RestifyRequest $request, $repositoryId)
    {
        $this->allowToUpdate($request);

        $this->resource = static::updatePlain($request->all(), $repositoryId);

        static::updated($this->resource);

        return $this->response()
            ->data($this->jsonSerialize())
            ->updated();
    }

    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $this->allowToDestroy($request);

        $status = static::destroyPlain($repositoryId);

        static::deleted($status);

        return $this->response()->deleted();
    }

    public function allowToUpdate(RestifyRequest $request, $payload = null)
    {
        $this->authorizeToUpdate($request);

        $validator = static::validatorForUpdate($request, $this, $payload);

        $validator->validate();
    }

    /**
     * @param RestifyRequest $request
     * @param array $payload
     * @return mixed
     */
    public function allowToStore(RestifyRequest $request, $payload = null)
    {
        static::authorizeToStore($request);

        $validator = static::validatorForStoring($request, $payload);

        $validator->validate();
    }

    public function allowToDestroy(RestifyRequest $request)
    {
        $this->authorizeToDelete($request);
    }

    public function allowToShow($request)
    {
        $this->authorizeToShow($request);
    }

    public static function storePlain(array $payload)
    {
        /** * @var RepositoryStoreRequest $request */
        $request = resolve(RepositoryStoreRequest::class);

        $request->attributes->add($payload);

        $repository = resolve(static::class);

        $repository->allowToStore($request, $payload);

        return DB::transaction(function () use ($request) {
            $model = static::fillWhenStore(
                $request, static::newModel()
            );

            $model->save();

            return $model;
        });
    }

    public static function updatePlain(array $payload, $id)
    {
        /** * @var RepositoryUpdateRequest $request */
        $request = resolve(RepositoryUpdateRequest::class);
        $request->attributes->add($payload);

        $model = $request->findModelQuery($id, static::uriKey())->lockForUpdate()->firstOrFail();

        /**
         * @var Repository
         */
        $repository = $request->newRepositoryWith($model, static::uriKey());

        $repository->allowToUpdate($request, $payload);

        return DB::transaction(function () use ($request, $repository) {
            $model = static::fillWhenUpdate($request, $repository->resource);

            $model->save();

            return $model;
        });
    }

    public static function showPlain($key)
    {
        /** * @var RestifyRequest $request */
        $request = resolve(RestifyRequest::class);

        /**
         * Dive into the Search service to attach relations.
         */
        $repository = $request->newRepositoryWith(tap($request->findModelQuery($key, static::uriKey())->firstOrFail(), function ($query) use ($request) {
            static::detailQuery($request, $query);
        }));

        $repository->allowToShow($request);

        return $repository->resource;
    }

    public static function destroyPlain($key)
    {
        /** * @var RepositoryDestroyRequest $request */
        $request = resolve(RepositoryDestroyRequest::class);

        $repository = $request->newRepositoryWith($request->findModelQuery($key, static::uriKey())->firstOrFail(), static::uriKey());

        $repository->allowToDestroy($request);

        return DB::transaction(function () use ($repository) {
            return $repository->resource->delete();
        });
    }

    /**
     * @param $model
     */
    public static function updated($model)
    {
        //
    }

    /**
     * @param int $status
     */
    public static function deleted($status)
    {
        //
    }

    public function response($content = '', $status = 200, array $headers = []): RestResponse
    {
        return new RestResponse($content, $status, $headers);
    }

    public function toArray(RestifyRequest $request): array
    {
        return [
            'id' => $this->when($this->resource instanceof Model, function () {
                return $this->resource->getKey();
            }),
            'type' => $this->model()->getTable(),
            'attributes' => $request->isDetailRequest() ? $this->resolveDetailsAttributes($request) : $this->resolveIndexAttributes($request),
            'relationships' => $this->when(value($related = $this->resolveRelationships($request)), $related),
            'meta' => $this->when(value($meta = $request->isDetailRequest() ? $this->resolveDetailsMeta($request) : $this->resolveIndexMeta($request)), $meta),
        ];
    }

    public function jsonSerialize()
    {
        $data = $this->toArray(Container::getInstance()->make(RestifyRequest::class));

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array)$data);
    }

    private function modelAttributes(Request $request = null): Collection
    {
        return collect(method_exists($this->resource, 'toArray') ? $this->resource->toArray() : []);
    }

    public static function stored($repository, $request)
    {
        //
    }
}
