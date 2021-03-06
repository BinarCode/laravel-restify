<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RepositoryFiltersRequest;
use Illuminate\Support\Collection;

class RepositoryFilterController extends RepositoryController
{
    public function __invoke(RepositoryFiltersRequest $request)
    {
        $repository = $request->repository();

        return data(
            $repository->collectAdvancedFilters($request)
                ->when($request->has('include'), function (Collection $collection) use ($repository, $request) {
                    return $collection->merge(
                        collect(str_getcsv($request->input('include')))->flatMap(fn ($key) => $repository::collectFilters($key))
                    );
                })
                ->when($request->has('only'), function (Collection $collection) use ($repository, $request) {
                    return collect(str_getcsv($request->input('only')))->flatMap(fn ($key) => $repository::collectFilters($key));
                })
        );
    }
}
