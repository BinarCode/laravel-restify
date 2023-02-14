<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Exceptions\Guard\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\Guard\GatePolicy;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Traits\ForwardsCalls;

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
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use ForwardsCalls;

    protected RestResponse $response;

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
     *
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
     *
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

    public function gate($policy, ...$objects): self
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

        return $this;
    }

    public function user(): ?Authenticatable
    {
        if (($this->guard instanceof Guard) === false) {
            $this->guard = app()->make(Guard::class);
        }

        if ($this->guard->check()) {
            return $this->guard->user();
        }

        return null;
    }

    public function message($msg): RestResponse
    {
        return $this->response()->message($msg);
    }

    /**
     * Returns with a list of errors.
     *
     * @return JsonResponse
     *
     * @throws BindingResolutionException
     */
    protected function errors(array $errors)
    {
        return $this->response()
            ->invalid()
            ->errors($errors);
    }

    protected function response($data = null, $status = 200, array $headers = []): RestResponse
    {
        if (empty($this->response)) {
            $this->response = new RestResponse($data, $status, $headers);
        }

        return $this->response;
    }

    public function __call($method, $parameters)
    {
        $this->response();

        $this->forwardCallTo($this->response, $method, $parameters);
    }
}
