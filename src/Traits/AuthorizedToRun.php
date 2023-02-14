<?php

namespace Binaryk\LaravelRestify\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait AuthorizedToRun
{
    /**
     * The callback used to authorize running the action.
     */
    public ?Closure $runCallback;

    /**
     * Determine if the action is executable for the given request.
     *
     * @param  Model  $model
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
}
