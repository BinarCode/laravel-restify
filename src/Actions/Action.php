<?php

namespace Binaryk\LaravelRestify\Actions;

use Binaryk\LaravelRestify\AuthorizedToSee;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\ProxiesCanSeeToGate;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Traits\Make;
use Binaryk\LaravelRestify\Transaction;
use Binaryk\LaravelRestify\Visibility;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Action extends RestController implements JsonSerializable
{
    use AuthorizedToSee, ProxiesCanSeeToGate, Make, Visibility;

    public static int $chunkCount = 200;

    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query;
    }

    /**
     * The callback used to authorize running the action.
     *
     * @var Closure|null
     */
    public ?Closure $runCallback;

    public function name()
    {
        return Restify::humanize($this);
    }

    /**
     * Get the URI key for the action.
     *
     * @return string
     */
    public function uriKey()
    {
        return Str::slug($this->name(), '-', null);
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param Request $request
     * @param Model $model
     * @return bool
     */
    public function authorizedToRun(Request $request, $model)
    {
        return $this->runCallback ? call_user_func($this->runCallback, $request, $model) : true;
    }

    /**
     * Set the callback to be run to authorize running the action.
     *
     * @param Closure $callback
     * @return $this
     */
    public function canRun(Closure $callback)
    {
        $this->runCallback = $callback;

        return $this;
    }

    /**
     * Get the payload available on the action.
     *
     * @return array
     */
    public function payload(): array
    {
        return [];
    }

//    abstract public function handle(ActionRequest $request, Collection $models): JsonResponse;

    public function handleRequest(ActionRequest $request)
    {
        if (!method_exists($this, 'handle')) {
            throw new Exception('Missing handle method from the action.');
        }

        $response = null;

        if (!$request->isForRepositoryRequest()) {
            $request->collectRepositories($this, static::$chunkCount, function ($models) use ($request, &$response) {
                Transaction::run(function () use ($models, $request, &$response) {
                    $response = $this->handle($request, $models);
                });
            });
        } else {
            Transaction::run(function () use ($request, &$response) {
                $response = $this->handle(
                    $request,
                    $request->findModelOrFail()
                );
            });
        }

        return $response;
    }

    public function jsonSerialize()
    {
        return array_merge([
            'name' => $this->name(),
            'destructive' => $this instanceof DestructiveAction,
            'uriKey' => $this->uriKey(),
            'payload' => $this->payload(),
        ]);
    }
}
