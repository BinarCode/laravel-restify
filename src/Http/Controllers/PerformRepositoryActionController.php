<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;

class PerformRepositoryActionController extends RepositoryController
{
    public function __invoke(RepositoryActionRequest $request)
    {
        //        $_SERVER['restify.requestClass'] = RepositoryActionRequest::class;

        $action = $request->action();

        if (is_callable($action)) {
            return $action($request);
        }

        return $action->handleRequest(
            $request,
        );
    }
}
