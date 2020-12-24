<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Closure;

class EnsureJsonApiHeaderMiddleware
{
    protected $acceptHeaders = [
        'application/vnd.api+json',
        'application/json',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
//        if (! collect($this->acceptHeaders)->contains($request->header('Accept'))) {
//            abort(400, 'Missing or invalid Accept header.');
//        }

        return $next($request);
    }
}
