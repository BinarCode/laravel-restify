<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;

class RepositoryShowController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request)
    {
        return $request->newRepositoryWith($request->findModelQuery()->firstOrFail())
            ->allowToShow($request)
            ->show($request, request('repositoryId'));
    }
}
