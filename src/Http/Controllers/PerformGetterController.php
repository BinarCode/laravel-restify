<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\GetterRequest;

class PerformGetterController extends RepositoryController
{
    public function __invoke(GetterRequest $request)
    {
        return $request->getter()?->handleRequest(
            $request,
        );
    }
}
