<?php

namespace Binaryk\LaravelRestify\Controllers;

use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Illuminate\Config\Repository;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Container\BindingResolutionException;
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
     * @var null
     */
    protected $response = null;

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
     * @param mixed $data
     * @param int $httpCode
     *
     * @return JsonResponse
     */
    protected function respond($data = null, $httpCode = 200)
    {
        $response = new \stdClass();
        $response->data = $data;

        return response()->json($response, $httpCode);
    }

    /**
     * Get Response object.
     *
     * @return RestResponse
     */
    protected function response()
    {
        if (empty($this->response)) {
            $this->response = new RestResponse();
        }

        return $this->response;
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
    protected function gate($policy, ...$objects)
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
            throw new GatePolicy('No access for this model.');
        }

        return true;
    }

    /**
     * @return Authenticatable|null
     * @throws BindingResolutionException
     */
    protected function user()
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
    protected function broker()
    {
        return Password::broker();
    }
}
