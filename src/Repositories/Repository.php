<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\DelegatesToResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class Repository implements RestifySearchable
{
    use InteractWithSearch,
        DelegatesToResource,
        ValidatingTrait,
        RepositoryFillFields;

    /**
     * This is named `resource` because of the forwarding properties from DelegatesToResource trait.
     * @var Model
     */
    public $resource;

    /**
     * Create a new resource instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function __construct($model)
    {
        $this->resource = $model;
    }

    /**
     * Get the underlying model instance for the resource.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model()
    {
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
    public function toArray()
    {
        $model = $this->model();

        return $model->toArray();
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
