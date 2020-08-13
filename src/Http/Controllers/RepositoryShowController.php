<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class RepositoryShowController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request)
    {
        /** * @var Repository $repository */
        $repository = $request->repository();

        return $request->newRepositoryWith(tap(
            tap($request->findModelQuery(), fn ($query) => $repository::mainQuery($request, $query->with($repository::getWiths()))),
            fn ($query) => $repository::showQuery($request, $query))->firstOrFail())
            ->allowToShow($request)
            ->show($request, request('repositoryId'));
    }
}
