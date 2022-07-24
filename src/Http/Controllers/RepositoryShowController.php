<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryShowRequest;
use Symfony\Component\HttpFoundation\Response;

class RepositoryShowController extends RepositoryController
{
    public function __invoke(RepositoryShowRequest $request): Response
    {
        $repository = $request->repository();

        return $request->repositoryWith(tap($request->modelQuery(), fn ($query) => $repository::showQuery(
            $request,
            $repository::mainQuery($request, $query->with($repository::withs()))
        ))->with($repository::withs())->firstOrFail())
            ->allowToShow($request)
            ->show($request, request('repositoryId'));
    }
}
