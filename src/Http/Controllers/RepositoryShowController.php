<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Illuminate\Http\JsonResponse;

class RepositoryShowController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request): JsonResponse
    {
        $repository = $request->repository();

        return $request->repositoryWith(tap(
            tap($request->modelQuery(), fn ($query) => $repository::detailQuery(
                $request,
                $repository::mainQuery($request, $query->with($repository::withs()))
            )),
            fn ($query) => $repository::showQuery($request, $query)
        )->firstOrFail())
            ->allowToShow($request)
            ->show($request, request('repositoryId'));
    }
}
