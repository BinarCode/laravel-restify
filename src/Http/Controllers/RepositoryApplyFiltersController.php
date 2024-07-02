<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryApplyFiltersRequest;

class RepositoryApplyFiltersController extends RepositoryController
{
    public function __invoke(RepositoryApplyFiltersRequest $request)
    {
        return $request->repository()->index($request);
    }
}
