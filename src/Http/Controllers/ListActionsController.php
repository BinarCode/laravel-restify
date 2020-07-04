<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;

class ListActionsController extends RepositoryController
{
    public function __invoke(ActionRequest $request)
    {
        return $this->response()->data(
            $request->newRepository()->availableActions($request)
        );
    }
}
