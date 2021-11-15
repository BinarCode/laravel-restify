<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Symfony\Component\HttpFoundation\Response;

class RepositoryStoreController extends RepositoryController
{
    public function __invoke(RepositoryStoreRequest $request): Response
    {
        return $request->repository()
            ->allowToStore($request)
            ->store($request);
    }
}
