<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Symfony\Component\HttpFoundation\Response;

class RepositoryUpdateController extends RepositoryController
{
    public function __invoke(RepositoryUpdateRequest $request): Response
    {
        $model = $request->modelQuery()->lockForUpdate()->firstOrFail();

        /** * @var Repository $repository */
        $repository = $request->repositoryWith($model);

        return $repository->allowToUpdate($request)->update($request, request('repositoryId'));
    }
}
