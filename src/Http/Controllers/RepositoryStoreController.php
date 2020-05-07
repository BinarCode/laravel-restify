<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;

class RepositoryStoreController extends RepositoryController
{
    public function __invoke(RepositoryStoreRequest $request)
    {
        return $request->repository()->allowToStore($request)->store($request);
    }
}
