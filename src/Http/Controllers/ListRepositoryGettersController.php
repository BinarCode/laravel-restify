<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryGetterRequest;
use Illuminate\Http\JsonResponse;

class ListRepositoryGettersController extends RepositoryController
{
    public function __invoke(RepositoryGetterRequest $request): JsonResponse
    {
        return data(
            $request->repository()->availableGetters($request)
        );
    }
}
