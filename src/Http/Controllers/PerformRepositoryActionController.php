<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;

class PerformRepositoryActionController extends RepositoryController
{
    public function __invoke(RepositoryActionRequest $request)
    {
//        $_SERVER['restify.requestClass'] = RepositoryActionRequest::class;

        $action = $request->action();

        return $action->handleRequest(
            $request,
        );
    }
}
