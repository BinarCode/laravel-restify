<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\ActionRequest;
use Illuminate\Http\JsonResponse;

class ListActionsController extends RepositoryController
{
    public function __invoke(ActionRequest $request): JsonResponse
    {
        return data(
            $request->repository()->availableActions($request)
        );
    }
}
