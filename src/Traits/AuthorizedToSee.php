<?php

namespace Binaryk\LaravelRestify\Traits;

use Closure;
use Illuminate\Http\Request;

trait AuthorizedToSee
{
    /**
     * The callback used to authorize viewing the filter or action.
     *
     * @var Closure|null
     */
    public $canSeeCallback;

    /**
     * Determine if the filter or action should be available for the given request.
     *
     * @return bool
     */
    public function authorizedToSee(Request $request)
    {
        return $this->canSeeCallback ? call_user_func($this->canSeeCallback, $request) : true;
    }

    /**
     * Set the callback to be run to authorize viewing the filter or action.
     *
     * @return self
     */
    public function canSee(Closure $callback)
    {
        $this->canSeeCallback = $callback;

        return $this;
    }
}
