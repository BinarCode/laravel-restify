<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RepositoryFiltersRequest;
use Illuminate\Support\Collection;

class RepositoryFilterController extends RepositoryController
{
    public function __invoke(RepositoryFiltersRequest $request)
    {
        $repository = $request->repository();

        return $this->response()->data(
            $repository->availableFilters($request)
                ->when($request->has('include'), function (Collection $collection) use ($repository, $request) {
                    return $collection->merge(
                        collect(str_getcsv($request->input('include')))->flatMap(fn($key) => $repository::collectFilters($key))
                    );
                })
                ->when($request->has('only'), function (Collection $collection) use ($repository, $request) {
                    return collect(str_getcsv($request->input('only')))->flatMap(fn($key) => $repository::collectFilters($key));
                })
        );
    }
}
