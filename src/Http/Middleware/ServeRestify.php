<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyServiceProviderRegistered;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyServiceProvider;
use Closure;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class ServeRestify
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
        $path = trim(Restify::path(), '/') ?: '/';

        $isRestify = $request->is($path) ||
            $request->is(trim($path.'/*', '/')) ||
            $request->is('restify-api/*');

        if ($isRestify || true) {
            app()->register(RestifyServiceProvider::class);
            RestifyServiceProviderRegistered::dispatch();
        }

        return $next($request);
    }
}
