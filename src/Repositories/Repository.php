<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Traits\PerformsQueries;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        Crudable,
        ResponseResolver,
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
        dd($this->model());
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
     * Resolve the repository to an array.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function resolve($request = null)
    {
        $data = $this->toArray(
            $request = $request ?: Container::getInstance()->make(RestifyRequest::class)
        );

        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        } elseif ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        return $this->filter((array)$data);
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
            'relationships' => $this->when(value($this->resolveDetailsRelationships($request)), $this->resolveDetailsRelationships($request)),
            'meta' => $this->when(value($this->resolveDetailsMeta($request)), $this->resolveDetailsMeta($request)),
        ];
    }

    public function jsonSerialize()
    {
        return $this->resolve();
    }
}
