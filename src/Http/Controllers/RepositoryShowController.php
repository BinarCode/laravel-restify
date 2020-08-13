<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;

class RepositoryShowController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request)
    {
        return $request->newRepositoryWith(tap(
            tap($request->findModelQuery(), fn($query) => $request->repository()::mainQuery($request, $query)),
            fn($query) => $request->repository()::showQuery($request, $query))->firstOrFail())
            ->allowToShow($request)
            ->show($request, request('repositoryId'));
    }
}
