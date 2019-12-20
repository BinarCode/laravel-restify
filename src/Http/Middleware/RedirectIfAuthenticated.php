<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Illuminate\Support\Facades\Auth;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RedirectIfAuthenticated
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
        if (Auth::guard($guard)->check()) {
            return false;
        }

        return $next($request);
    }
}
