<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

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
        return $request->newRepositoryWith($request->findModelQuery())->show($request);
    }
}
