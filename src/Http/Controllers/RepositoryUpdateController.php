<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class RepositoryUpdateController extends RepositoryController
{
    public function __invoke(RepositoryUpdateRequest $request)
    {
        $model = $request->findModelQuery()->lockForUpdate()->firstOrFail();

        /** * @var Repository $repository */
        $repository = $request->newRepositoryWith($model);

        return $repository->allowToUpdate($request)->update($request, request('repositoryId'));
    }
}
