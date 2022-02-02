<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryGetterRequest;

class PerformRepositoryGetterController extends RepositoryController
{
    public function __invoke(RepositoryGetterRequest $request)
    {
        return $request->getter()?->handleRequest(
            $request,
        );
    }
}
