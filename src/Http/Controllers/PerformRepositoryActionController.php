<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;

class PerformRepositoryActionController extends RepositoryController
{
    public function __invoke(ActionRequest $request)
    {
        $action = $request->action();

        return $action->handle(
            $request,
            collect(
                [
                    $request->findModelQuery()->firstOrFail(),
                ]
            )
        );
    }
}
