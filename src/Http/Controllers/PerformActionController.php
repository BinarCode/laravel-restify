<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;

class PerformActionController extends RepositoryController
{
    public function __invoke(ActionRequest $request)
    {
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
