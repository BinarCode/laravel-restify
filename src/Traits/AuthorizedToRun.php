<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait AuthorizedToRun
{
    /**
     * The callback used to authorize running the action.
     */
    public ?Closure $runCallback = null;

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  ?Model  $model
     * @return bool
     */
    public function authorizedToRun(Request $request, $model)
    {
        return $this->runCallback ? call_user_func($this->runCallback, $request, $model) : true;
    }

    /**
     * Set the callback to be run to authorize running the action.
     *
     * @return $this
     */
    public function canRun(Closure $callback)
    {
        $this->runCallback = $callback;

        return $this;
    }

    /**
     * Authorize running the action, aborting the request when it is denied.
     *
     * @throws UnauthorizedException
     */
    protected function authorizeRun(Request $request, ?Model $model): void
    {
        if (! $this->authorizedToRun($request, $model)) {
            throw UnauthorizedException::make($this->authorizeRunMessage());
        }
    }

    /**
     * Authorize running on the single model of a show action or show getter: the user must be allowed to
     * show that model, as the show route requires.
     *
     * @throws UnauthorizedException
     */
    protected function authorizeShownModel(RestifyRequest $request, Model $model): void
    {
        if (! $request->repositoryWith($model)->authorizedToShow($request)) {
            throw UnauthorizedException::make($this->authorizeRunMessage());
        }
    }

    /**
     * The message used when a run authorization check fails.
     */
    protected function authorizeRunMessage(): string
    {
        return 'Not authorized to run this action.';
    }
}
