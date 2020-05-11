<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryFiltersRequest;

class RepositoryFilterController extends RepositoryController
{
    public function __invoke(RepositoryFiltersRequest $request)
    {
        $repository = $request->repository();

        return $this->response()->data($repository->availableFilters($request));
    }
}
