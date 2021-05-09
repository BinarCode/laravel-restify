<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryActionRequest;
use Illuminate\Http\JsonResponse;

class ListRepositoryActionsController extends RepositoryController
{
    public function __invoke(RepositoryActionRequest $request): JsonResponse
    {
        return data(
            $request->repository()->availableActions($request)
        );
    }
}
