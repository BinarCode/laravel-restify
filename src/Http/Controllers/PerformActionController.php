<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;

class PerformActionController extends RepositoryController
{
    public function __invoke(ActionRequest $request)
    {
        $request->validate([
            'repositories' => 'required',
        ]);

        $action = $request->action();

        return $action->handleRequest(
            $request,
        );
    }
}
