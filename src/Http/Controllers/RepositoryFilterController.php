<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

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
                // After
                ->when($request->has('include'), function (Collection $collection) use ($repository, $request) {
                    return $collection->merge(
                        collect(str_getcsv($request->input('include')))->map(fn ($key) => collect([
                            SearchableFilter::uriKey() => SearchableFilter::class,
                            MatchFilter::uriKey() => MatchFilter::class,
                            SortableFilter::uriKey() => SortableFilter::class,
                        ])->get($key))->flatMap(fn ($filterable) => $filterable::makeForRepository($repository))
                    );
                })
                ->when($request->has('only'), function (Collection $collection) use ($repository, $request) {
                    return collect(str_getcsv($request->input('only')))->map(fn ($key) => collect([
                        SearchableFilter::uriKey() => SearchableFilter::class,
                        MatchFilter::uriKey() => MatchFilter::class,
                        SortableFilter::uriKey() => SortableFilter::class,
                    ])->get($key))->flatMap(fn ($filterable) => $filterable::makeForRepository($repository));
                })
        );
    }
}
