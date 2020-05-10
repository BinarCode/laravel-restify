<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\GlobalSearchRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\GlobalSearch;

class GlobalSearchController extends RepositoryController
{
    public function __invoke(GlobalSearchRequest $request)
    {
        $results = (new GlobalSearch(
            $request, Restify::globallySearchableRepositories($request)
        ))->get();

        return $this->response()->data($results);
    }
}
