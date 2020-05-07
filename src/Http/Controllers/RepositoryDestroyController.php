<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;

class RepositoryDestroyController extends RepositoryController
{
    public function __invoke(RepositoryDestroyRequest $request)
    {
        $repository = $request->newRepositoryWith($request->findModelQuery()->firstOrFail());

        return $repository->destroy($request, request('repositoryId'));
    }
}
