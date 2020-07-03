<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest;

class RepositoryStoreBulkController extends RepositoryController
{
    public function __invoke(RepositoryStoreBulkRequest $request)
    {
        return $request->repository()
            ->allowToBulkStore($request)
            ->storeBulk($request);
    }
}
