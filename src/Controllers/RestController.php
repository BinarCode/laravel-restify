<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Password;
use Throwable;

/**
 * Abstract Class RestController.
 *
 * This class provides reusable, high-level RESTful
 * functionality that subclasses can take advantage
 * of.
 *
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
abstract class RestController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var RestResponse
     */
    protected $response;

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @var RestifyRequest
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Guard
     */
    protected $guard;

    /**
     * @return RestifyRequest
     * @throws BindingResolutionException
     */
    public function request()
    {
        $container = Container::getInstance();

        if (($this->request instanceof RestifyRequest) === false) {
            $this->request = $container->make(RestifyRequest::class);
        }

        return $this->request;
    }

    /**
     * @return Config
     * @throws BindingResolutionException
     */
    public function config()
    {
        $container = Container::getInstance();

        if (($this->config instanceof Config) === false) {
            $this->config = $container->make(Config::class);
        }

        return $this->config;
    }

    /**
     * Returns a generic response to the client.
     *
     * @param  mixed  $data
     * @param  int  $httpCode
     *
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    protected function respond($data = null, $httpCode = 200)
    {
        $response = new \stdClass();
        $response->data = $data;

        return $this->response()->data($data)->code($httpCode)->respond();
    }

    /**
     * Get Response object.
     *
     * @param  null  $data
     * @param  int  $status
     * @param  array  $headers
     * @return RestResponse
     */
    protected function response($data = null, $status = 200, array $headers = [])
    {
        if (empty($this->response)) {
            $this->response = new RestResponse($data, $status, $headers);
        }

        return $this->response;
    }

    /**
     * @param $modelClass
     * @param  array  $filters
     * @return array
     * @throws BindingResolutionException
     * @throws InstanceOfException
     * @throws Throwable
     */
    public function search($modelClass, $filters = [])
    {
        $paginator = $this->paginator($modelClass, $filters);

        if ($modelClass instanceof Repository) {
            $items = $paginator->getCollection()->mapInto(get_class($modelClass))->map->toArray($this->request());
        } else {
            $items = $paginator->getCollection();
        }

        return [
            'meta' => Arr::except($paginator->toArray(), ['data', 'next_page_url', 'last_page_url', 'first_page_url', 'prev_page_url', 'path']),
            'links' => Arr::only($paginator->toArray(), ['next_page_url', 'last_page_url', 'first_page_url', 'prev_page_url', 'path']),
            'data' => $items,
        ];
    }

    /**
     * @param $modelClass
     * @param  array  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws BindingResolutionException
     * @throws InstanceOfException
     * @throws Throwable
     */
    public function paginator($modelClass, $filters = [])
    {
        $results = SearchService::instance()
            ->setPredefinedFilters($filters)
            ->search($this->request(), $modelClass instanceof Repository ? $modelClass->model() : new $modelClass);

        $results->tap(function ($query) use ($modelClass) {
            if ($modelClass instanceof Repository) {
                $modelClass::indexQuery($this->request(), $query);
            }
        });

        return $results->paginate($this->request()->get('perPage') ?? ($modelClass::$defaultPerPage ?? RestifySearchable::DEFAULT_PER_PAGE));
    }

    /**
     * @param $policy
     * @param $objects
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     * @throws GatePolicy
     * @throws BindingResolutionException
     */
    public function gate($policy, ...$objects)
    {
        foreach ($objects as $object) {
            if ($object === null) {
                throw new EntityNotFoundException($policy);
            }
        }

        if (($this->gate instanceof Gate) === false) {
            $this->gate = app()->make(Gate::class);
        }

        if ($this->gate->check($policy, $objects) === false) {
            throw new GatePolicy(__('messages.no_model_access'));
        }

        return true;
    }

    /**
     * @return Authenticatable|null
     * @throws BindingResolutionException
     */
    public function user()
    {
        if (($this->guard instanceof Guard) === false) {
            $this->guard = app()->make(Guard::class);
        }

        if ($this->guard->check()) {
            return $this->guard->user();
        }
    }

    /**
     * @return PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Returns with a message.
     * @param $msg
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function message($msg)
    {
        return $this->response()
            ->message($msg)
            ->respond();
    }

    /**
     * Returns with a list of errors.
     *
     * @param  array  $errors
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    protected function errors(array $errors)
    {
        return $this->response()
            ->invalid()
            ->errors($errors)
            ->respond();
    }
}
