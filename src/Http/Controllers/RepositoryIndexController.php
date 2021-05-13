<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryIndexRequest;
use Illuminate\Http\JsonResponse;

class RepositoryIndexController extends RepositoryController
{
    public function __invoke(RepositoryIndexRequest $request): JsonResponse
    {
        return $request->repository()->index($request);
    }
}
