<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Traits\PerformsQueries;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        ResponseResolver;

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
    public function __construct($model)
    {
        parent::__construct($model);
        $this->resource = $model;
    }

    /**
     * Get the underlying model instance for the resource.
     *
     * @return \Illuminate\Database\Eloquent\Model|LengthAwarePaginator
     */
    public function model()
    {
        if ($this->isRenderingCollection() || $this->isRenderingPaginated()) {
            return $this->modelFromIterator();
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
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function toArray($request)
    {
        $request = Container::getInstance()->make('request');

        if ($this->isRenderingCollection()) {
            return $this->toArrayForCollection($request);
        }

        $serialized = [
            'id' => $this->when($this->isRenderingRepository(), function () {
                return $this->getKey();
            }),
            'type' => method_exists($this, 'uriKey') ? static::uriKey() : Str::plural(Str::kebab(class_basename(get_called_class()))),
            'attributes' => $this->resolveDetailsAttributes($request),
            'relationships' => $this->when(value($this->resolveDetailsRelationships($request)), $this->resolveDetailsRelationships($request)),
            'meta' => $this->when(value($this->resolveDetailsMeta($request)), $this->resolveDetailsMeta($request)),
        ];

        return $this->resolveDetails($serialized);
    }

    abstract public function fields(RestifyRequest $request);

    /**
     * @param  RestifyRequest  $request
     * @return Collection
     */
    public function collectFields(RestifyRequest $request)
    {
        return collect($this->fields($request));
    }
}
