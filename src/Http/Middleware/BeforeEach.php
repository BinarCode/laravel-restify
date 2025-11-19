<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Closure;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class BeforeEach
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        RestifyBeforeEach::dispatch($request);

        return $next($request);
    }
}
