<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;

class PerformRepositoryActionController extends RepositoryController
{
    public function __invoke(RepositoryActionRequest $request)
    {
        $action = $request->action();

        return $action->handleRequest(
            $request,
        );
    }
}
