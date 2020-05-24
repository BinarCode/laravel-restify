<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\Post;

use Illuminate\Http\Request;

class PostAbortMiddleware
{
    public function handle(Request $request, $next)
    {
        abort(404);

        $next($request);
    }
}
