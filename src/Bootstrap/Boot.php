<?php

namespace Binaryk\LaravelRestify\Bootstrap;

use Binaryk\LaravelRestify\Events\RestifyBeforeEach;
use Illuminate\Http\Request;

class Boot
{
    public function __construct(
        private Request $request,
        private RoutesBoot $routesBoot,
        private CustomRoutesBoot $customRoutesBoot,
    ) {
    }

    public function boot(): void
    {
        $this->customRoutesBoot->boot();

//        if (isRestify($request = $this->request)) {
            RestifyBeforeEach::dispatch($request);
            $this->routesBoot->boot();
//        }
    }
}
