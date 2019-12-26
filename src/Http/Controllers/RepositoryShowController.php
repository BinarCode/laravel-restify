<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Services\Search\SearchService;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryShowController extends RepositoryController
{
    /**
     * @param  RestifyRequest  $request
     * @return \Binaryk\LaravelRestify\Controllers\RestResponse|mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function handle(RestifyRequest $request)
    {
        /**
         * @var Repository
         */
        $repository = $request->newRepositoryWith(tap(SearchService::instance()->prepareRelations($request, $request->findModelQuery()), function ($query) use ($request) {
            $request->newRepository()->detailQuery($request, $query);
        })->firstOrFail());

        $repository->authorizeToView($request);

        return $repository;
    }
}
