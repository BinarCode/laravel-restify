<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Binaryk\LaravelRestify\Events\RestifyServiceProviderRegistered;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\RestifyCustomRoutesProvider;
use Binaryk\LaravelRestify\RestifyServiceProvider;
use Closure;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RestifyInjector
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = trim(Restify::path(), '/') ?: '/';

        $isRestify = $request->is($path) ||
            $request->is(trim($path . '/*', '/')) ||
            $request->is('restify-api/*') ||
            collect(Restify::$repositories)
                ->filter(fn($repository) => $repository::prefix())
                ->some(fn($repository) => $request->is($repository::prefix() . '/*'));

        app()->register(RestifyCustomRoutesProvider::class);

        if ($isRestify) {
            RestifyBeforeEach::dispatch($request);
            app()->register(RestifyServiceProvider::class);
            RestifyServiceProviderRegistered::dispatch();
        }

        return $next($request);
    }
}
