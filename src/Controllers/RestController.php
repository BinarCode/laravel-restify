<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Illuminate\Config\Repository;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Password;

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
     * @var Request
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
     * @return Request
     * @throws BindingResolutionException
     */
    public function request()
    {
        if (($this->request instanceof Request) === false) {
            $this->request = app()->make(Request::class);
        }

        return $this->request;
    }

    /**
     * @return Config
     * @throws BindingResolutionException
     */
    public function config()
    {
        if (($this->config instanceof Repository) === false) {
            $this->config = app()->make(Repository::class);
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
     */
    public function search($modelClass, $filters = [])
    {
        $container = Container::getInstance();

        /** * @var SearchService $searchService */
        $searchService = $container->make(SearchService::class);
        $results = $searchService
            ->setPredefinedFilters($filters)
            ->search($this->request(), ($modelClass instanceof Model ? $modelClass : $container->make($modelClass)));

        $paginator = $results->paginate($this->request()->get('perPage') ?? ($modelClass::$defaultPerPage ?? RestifySearchable::DEFAULT_PER_PAGE));
        $items = $paginator->getCollection()->map->serializeForIndex($this->request());

        return array_merge($paginator->toArray(), [
            'data' => $items,
        ]);
    }

    public function index(Request $request, $model = null)
    {
        $data = $this->paginator($model)->getCollection()->map->serializeForIndex($this->request());

        return $this->respond($data);
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
     * @return JsonResponse
     */
    protected function errors(array $errors)
    {
        return $this->response()
            ->invalid()
            ->errors($errors)
            ->respond();
    }
}
