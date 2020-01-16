<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Traits\PerformsQueries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * This class serve as repository collection and repository single model
 * This allow you to use all of the Laravel default repositories features (as adding headers in the response, or customizing
 * response).
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class Repository extends RepositoryCollection implements RestifySearchable
{
    use InteractWithSearch,
        ValidatingTrait,
        RepositoryFillFields,
        PerformsQueries,
        Crudable;

    /**
     * This is named `resource` because of the forwarding properties from DelegatesToResource trait.
     * This may be a single model or a illuminate collection, or even a paginator instance.
     *
     * @var Model|LengthAwarePaginator
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function __construct($model = null)
    {
        parent::__construct($model);
    }

    /**
     * Get the underlying model instance for the resource.
     *
     * @return \Illuminate\Database\Eloquent\Model|LengthAwarePaginator
     */
    public function model()
    {
        if ($this->isRenderingCollection() || $this->isRenderingPaginated()) {
            return $this->modelFromIterator() ?? static::newModel();
        }

        return $this->resource;
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return Str::plural(Str::kebab(class_basename(get_called_class())));
    }

    /**
     * Get a fresh instance of the model represented by the resource.
     *
     * @return mixed
     */
    public static function newModel()
    {
        $model = static::$model;

        return new $model;
    }

    /**
     * @return Builder
     */
    public static function query()
    {
        return static::newModel()->query();
    }

    /**
     * @return array
     */
    public function toArray($request)
    {
        if ($this->isRenderingCollection()) {
            return $this->toArrayForCollection($request);
        }

        $serialized = [
            'id' => $this->when($this->isRenderingRepository(), function () {
                return $this->getKey();
            }),
            'type' => self::model()->getTable(),
            'attributes' => $this->resolveDetailsAttributes($request),
            'relationships' => $this->when(value($this->resolveDetailsRelationships($request)), $this->resolveDetailsRelationships($request)),
            'meta' => $this->when(value($this->resolveDetailsMeta($request)), $this->resolveDetailsMeta($request)),
        ];

        return $this->serializeDetails($request, $serialized);
    }

    /**
     * Resolvable attributes before storing/updating.
     *
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [];
    }

    /**
     * @param  RestifyRequest  $request
     * @return Collection
     */
    public function collectFields(RestifyRequest $request)
    {
        return collect($this->fields($request))->filter(function (Field $field) {
            return $field->filter();
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
     * @param $model
     * @return Repository
     */
    public static function resolveWith($model)
    {
        /**
         * @var Repository
         */
        $self = resolve(static::class);

        return $self->withResource($model);
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
        return (new static)->$method(...$parameters);
    }

    /**
     * Defining custom roues.
     * The prefix of this route is the uriKey (e.g. 'restify-api/orders'),
     * The namespace is Http/Controllers
     * Middlewares are the same from config('restify.middleware').
     *
     * However all options could be customized by passing an $options argument
     *
     * @param  Router  $router
     * @param $options
     */
    public static function routes(Router $router, $options = [])
    {
        // override for custom routes
    }
}
