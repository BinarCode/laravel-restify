<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait Crudable
{
    /**
     * @param  null  $request
     * @return \Illuminate\Http\JsonResponse
     */
    abstract public function response($request = null);

    /**
     * @param  RestifyRequest  $request
     * @param  Paginator  $paginated
     * @return JsonResponse
     */
    public function index(RestifyRequest $request, Paginator $paginated)
    {
        return static::resolveWith($paginated)->response();
    }

    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function show(RestifyRequest $request, $repositoryId)
    {
        /**
         * Dive into the Search service to attach relations.
         */
        $this->withResource(tap($this->resource, function ($query) use ($request) {
            $request->newRepository()->detailQuery($request, $query);
        })->firstOrFail());

        $this->authorizeToView($request);

        return $this->response($request);
    }

    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     */
    public function store(RestifyRequest $request)
    {
        $model = DB::transaction(function () use ($request) {
            $model = self::fillWhenStore(
                $request, self::newModel()
            );

            $model->save();

            return $model;
        });

        return (new static ($model))
            ->response()
            ->setStatusCode(RestResponse::REST_RESPONSE_CREATED_CODE)
            ->header('Location', Restify::path().'/'.static::uriKey().'/'.$model->id);
    }

    /**
     * @param  RestifyRequest  $request
     * @param $model
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws ValidationException
     */
    public function update(RestifyRequest $request, $repositoryId)
    {
        $this->allowToUpdate($request);

        DB::transaction(function () use ($request) {
            $model = static::fillWhenUpdate($request, $this->resource);

            $model->save();

            return $this;
        });

        return $this->response()->setStatusCode(RestResponse::REST_RESPONSE_UPDATED_CODE);
    }

    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $this->allowToDestroy($request);

        DB::transaction(function () use ($request) {
            return $this->resource->delete();
        });

        return $this->response()
            ->setStatusCode(RestResponse::REST_RESPONSE_DELETED_CODE);
    }

    /**
     * @param  RestifyRequest  $request
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws ValidationException
     */
    public function allowToUpdate(RestifyRequest $request)
    {
        $this->authorizeToUpdate($request);

        $validator = static::validatorForUpdate($request, $this);

        $validator->validate();
    }

    /**
     * @param  RestifyRequest  $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowToDestroy(RestifyRequest $request)
    {
        $this->authorizeToDelete($request);
    }
}
