<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyStarting;
use Closure;
use Illuminate\Http\Request;

/**
 * One of the most important middleware, because at the RestifyServing
 * callback we load routes, add gate for seeing restify, add exception handler.
 *
 * This middleware is put manually into the middlewares list in the config
 *
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class DispatchRestifyStartingEvent
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        RestifyStarting::dispatch($request);

        return $next($request);
    }
}
