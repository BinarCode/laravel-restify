<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RepositoryFiltersRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RepositoryFilterController extends RepositoryController
{
    public function __invoke(RepositoryFiltersRequest $request)
    {
        $repository = $request->repository();

        return $this->response()->data(
            $repository->availableFilters($request)
                ->when(Str::contains($request->input('include'), 'matches'), function (Collection $collection) use ($repository) {
                    return $collection->merge(
                        MatchFilter::makeForRepository($repository)
                    );
                })
                ->when(Str::contains($request->input('include'), 'sortable'), function (Collection $collection) use ($repository) {
                    return $collection->merge(
                        SortableFilter::makeForRepository($repository)
                    );
                })
        );
    }
}
