<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryDestroyController extends RepositoryController
{
    /**
     * @param  RepositoryDestroyRequest  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws BindingResolutionException
     * @throws EntityNotFoundException
     * @throws Throwable
     * @throws UnauthorizedException
     */
    public function handle(RepositoryDestroyRequest $request)
    {
        /**
         * @var Repository
         */
        $repository = $request->newRepository();

        $repository->authorizeToDelete($request);

        DB::transaction(function () use ($request, $repository) {
            $model = $request->findModelQuery();
            return $model->delete();
        });

        return $this->response()
            ->code(204)
            ->respond();
    }
}
