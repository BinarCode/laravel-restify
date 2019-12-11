<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Repositories\Contracts\RestifyRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * RestifyRepository class to communicate with data sources of different kinds
 *
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class RestifyRepository implements RestifyRepositoryInterface
{
    /**
     * Holds injected model
     *
     * @var EloquentModel
     */
    protected $model;


    /**
     * Class construct
     *
     * Injected models should be an instance of Model\Model
     *
     * @param EloquentModel $model
     */
    public function __construct(EloquentModel $model)
    {
        $this->model = $model;
    }

    /**
     * Grabs any uncaught method calls
     *
     * @param string $methodName
     * @param array $params
     * @return mixed
     */
    public function __call($methodName, $params)
    {
        return call_user_func_array([$this->model, $methodName], $params);
    }

    /**
     * Static magic implementation
     *
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($methodName, $arguments)
    {
        $class = get_called_class();

        return call_user_func($class . '::' . $methodName, $arguments);
    }

    /**
     * @return Builder
     */
    public function query(): Builder
    {
        return $this->model->query();
    }

}
