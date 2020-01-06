<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Services\Search\SearchService;

/**
 * @package Binaryk\LaravelRestify\Repositories;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait Crudable
{
    /**
     * @param  RestifyRequest  $request
     * @return Repository
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function show(RestifyRequest $request)
    {
        $repository = $request->newRepositoryWith(tap(SearchService::instance()->prepareRelations($request, $request->findModelQuery()), function ($query) use ($request) {
            $request->newRepository()->detailQuery($request, $query);
        })->firstOrFail());

        $repository->authorizeToView($request);

        return $repository;
    }
}
