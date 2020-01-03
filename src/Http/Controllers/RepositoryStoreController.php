<?php

namespace Binaryk\LaravelRestify\Http\Controllers;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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

        $repository::authorizeToCreate($request);

        $validator = $repository::validatorForStoring($request);

        if ($validator->fails()) {
            return $this->response()->invalid()->errors($validator->errors()->toArray())->respond();
        }

        $model = DB::transaction(function () use ($request, $repository) {
            [$model] = $repository::fillWhenStore(
                $request, $repository::newModel()
            );

            $model->save();

            return $model;
        });

        return $this->response()
            ->code(RestResponse::REST_RESPONSE_CREATED_CODE)
            ->forRepository($request->newRepositoryWith($model), true)
            ->header('Location', Restify::path().'/'.$repository::uriKey().'/'.$model->id)
            ->respond();
    }
}
