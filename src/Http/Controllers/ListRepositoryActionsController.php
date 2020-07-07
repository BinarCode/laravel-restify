<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;

class ListRepositoryActionsController extends RepositoryController
{
    public function __invoke(RepositoryActionRequest $request)
    {
        return $this->response()->data(
            $request->newRepository()->availableActions($request)
        );
    }
}
