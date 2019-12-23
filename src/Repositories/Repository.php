<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class Repository implements RestifySearchable
{
    use InteractWithSearch;

    /**
     * @var Model
     */
    public $modelInstance;

    /**
     * Create a new resource instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function __construct($model)
    {
        $this->modelInstance = $model;
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
