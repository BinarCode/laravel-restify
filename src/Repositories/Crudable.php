<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Illuminate\Support\Facades\DB;

/**
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

    /**
     * @param  RestifyRequest  $request
     * @return mixed
     */
    public function store(RestifyRequest $request)
    {
        $model = DB::transaction(function () use ($request) {
            [$model] = self::fillWhenStore(
                $request, self::newModel()
            );

            $model->save();

            return $model;
        });

        return (new static ($model))
            ->response()
            ->setStatusCode(RestResponse::REST_RESPONSE_CREATED_CODE)
            ->header('Location', Restify::path() . '/' . self::uriKey() . '/' . $model->id);
    }

    /**
     * @param  RestifyRequest  $request
     * @param $model
     * @return mixed
     */
    public function update(RestifyRequest $request, $model)
    {
        DB::transaction(function () use ($request, $model) {
            [$model] = static::fillWhenUpdate($request, $model);

            $model->save();

            return $this;
        });

        return $this->response()->setStatusCode(RestResponse::REST_RESPONSE_UPDATED_CODE);
    }

    /**
     * @param  RestifyRequest  $request
     * @return mixed
     */
    public function destroy(RestifyRequest $request)
    {
        DB::transaction(function () use ($request) {
            $model = $request->findModelQuery();

            return $model->delete();
        });

        return $this->response()
            ->setStatusCode(RestResponse::REST_RESPONSE_DELETED_CODE);
    }

}
