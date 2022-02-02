<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\GetterRequest;
use Illuminate\Http\JsonResponse;

class ListGettersController extends RepositoryController
{
    public function __invoke(GetterRequest $request): JsonResponse
    {
        return data(
            $request->repository()->availableGetters($request)
        );
    }
}
