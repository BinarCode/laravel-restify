<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\GlobalSearchRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\GlobalSearch;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends RepositoryController
{
    public function __invoke(GlobalSearchRequest $request): JsonResponse
    {
        $results = (new GlobalSearch(
            $request,
            Restify::globallySearchableRepositories($request)
        ))->get();

        return data($results);
    }
}
