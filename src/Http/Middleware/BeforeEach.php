<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Binaryk\LaravelRestify\Events\RestifyServiceProviderRegistered;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyServiceProvider;
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
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        RestifyBeforeEach::dispatch($request);
        return $next($request);
    }
}
