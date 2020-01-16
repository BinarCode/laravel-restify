<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryStoreController extends RepositoryController
{
    /**
     * @param  RepositoryStoreRequest  $request
     * @return JsonResponse
     * @throws BindingResolutionException
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function handle(RepositoryStoreRequest $request)
    {
        /**
         * @var Repository
         */
        $repository = $request->repository();

        return $request->newRepositoryWith($repository::newModel())->store($request);
    }
}
