<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\IndexRepositoryActionRequest;

class PerformActionController extends RepositoryController
{
    public function __invoke(IndexRepositoryActionRequest $request)
    {
        $_SERVER['restify.requestClass'] = IndexRepositoryActionRequest::class;

        $action = $request->action();

        if (! $action->isStandalone()) {
            $request->validate([
                'repositories' => 'required',
            ]);
        }

        return $action->handleRequest(
            $request,
        );
    }
}
