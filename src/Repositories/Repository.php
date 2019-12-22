<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Traits\AuthorizableModels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class Repository
{
    use AuthorizableModels;

    /**
     * @var Model
     */
    public $modelInstance;

    /**
     * Create a new resource instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $resource
     */
    public function __construct($resource)
    {
        $this->modelInstance = $resource;
    }

    /**
     * Get the underlying model instance for the resource.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model()
    {
        return $this->modelInstance;
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
}
