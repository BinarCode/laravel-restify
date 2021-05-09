<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Illuminate\Http\JsonResponse;

class RepositoryStoreController extends RepositoryController
{
    public function __invoke(RepositoryStoreRequest $request): JsonResponse
    {
        return $request->repository()
            ->allowToStore($request)
            ->store($request);
    }
}
