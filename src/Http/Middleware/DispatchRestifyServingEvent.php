<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyServing;
use Closure;

/**
 * One of the most important middleware, because at the RestifyServing
 * callback we load routes, add gate for seeing restify, add exception handler.
 *
 * This middleware is put manually into the middlewares list in the config
 *
 * @package Binaryk\LaravelRestify\Middleware;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class DispatchRestifyServingEvent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        RestifyServing::dispatch($request);

        return $next($request);
    }
}
