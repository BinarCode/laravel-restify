<?php

namespace Binaryk\LaravelRestify\Http\Middleware;

use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorizeRestify
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return Response
     *
     * @throws UnauthorizedException
     */
    public function handle($request, $next)
    {
        if (Restify::check($request)) {
            return $next($request);
        }

        abort(401, __('Unauthorized to view restify.'));
    }
}
