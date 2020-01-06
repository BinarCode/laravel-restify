<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
class RepositoryUpdateController extends RepositoryController
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
    public function handle(RepositoryUpdateRequest $request)
    {
        $model = $request->findModelQuery()->lockForUpdate()->firstOrFail();

        /**
         * @var Repository
         */
        $repository = $request->newRepositoryWith($model);
        $repository->authorizeToUpdate($request);
        $validator = $repository::validatorForUpdate($request, $repository);

        if ($validator->fails()) {
            return $this->response()->invalid()->errors($validator->errors()->toArray())->respond();
        }

        $repository = DB::transaction(function () use ($request, $repository, $model) {
            [$model] = $repository::fillWhenUpdate($request, $model);

            $model->save();

            return $repository;
        });

        return $this->response()->forRepository($repository)->code(RestResponse::REST_RESPONSE_DELETED_CODE)->respond();
    }
}
