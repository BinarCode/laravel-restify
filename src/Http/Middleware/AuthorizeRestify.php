<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Restify;

class AuthorizeRestify
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws UnauthorizedExceptio
     * n
     */
    public function handle($request, $next)
    {
        if (Restify::check($request)) {
            return $next($request);
        }

        abort(401, __('Unauthorized to view restify.'));
    }
}
