<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait Crudable
{
    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     * @throws \Binaryk\LaravelRestify\Exceptions\InstanceOfException
     * @throws \Throwable
     */
    public function index(RestifyRequest $request)
    {
        $results = SearchService::instance()->search($request, $this->model());

        $results = $results->tap(function ($query) use ($request) {
            self::indexQuery($request, $query);
        });

        /**
         * @var AbstractPaginator $paginator
         */
        $paginator = $results->paginate($request->get('perPage') ?? (static::$defaultPerPage ?? RestifySearchable::DEFAULT_PER_PAGE));

        $items = $paginator->getCollection()->map(function ($value) {
            return static::resolveWith($value);
        });

        try {
            $this->allowToViewAny($request, $items);
        } catch (UnauthorizedException|AuthorizationException $e) {
            return $this->response()->forbidden()->addError($e->getMessage());
        }

        // Filter out items the request user don't have enough permissions for show
        $items = $items->filter(function ($repository) use ($request) {
            return $repository->authorizedToShow($request);
        });

        return $this->response([
            'meta' => RepositoryCollection::meta($paginator->toArray()),
            'links' => RepositoryCollection::paginationLinks($paginator->toArray()),
            'data' => $items,
        ]);
    }

    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     */
    public function show(RestifyRequest $request, $repositoryId)
    {
        /**
         * Dive into the Search service to attach relations.
         */
        $this->withResource(tap($this->resource, function ($query) use ($request) {
            static::detailQuery($request, $query);
        })->firstOrFail());

        try {
            $this->allowToShow($request);
        } catch (AuthorizationException $e) {
            return $this->response()->forbidden()->addError($e->getMessage());
        }


        return $this->response($this->jsonSerialize());
    }

    /**
     * @param  RestifyRequest  $request
     * @return JsonResponse
     */
    public function store(RestifyRequest $request)
    {
        try {
            $this->allowToStore($request);
        } catch (AuthorizationException | UnauthorizedException $e) {
            return $this->response()->errors([
                'errors' => Arr::wrap($e->getMessage()),
            ])->code(RestResponse::REST_RESPONSE_FORBIDDEN_CODE);
        } catch (ValidationException $e) {
            return $this->response()->errors($e->errors())
                ->code(RestResponse::REST_RESPONSE_INVALID_CODE);
        }

        $model = DB::transaction(function () use ($request) {
            $model = self::fillWhenStore(
                $request, self::newModel()
            );

            $model->save();

            return $model;
        });

        return $this->response(static::resolveWith($model)->jsonSerialize(), RestResponse::REST_RESPONSE_CREATED_CODE)
            ->header('Location', Restify::path() . '/' . static::uriKey() . '/' . $model->id);
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

        return response()->json($this->jsonSerialize(), RestResponse::REST_RESPONSE_UPDATED_CODE);
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
     * @return mixed
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws ValidationException
     */
    public function allowToStore(RestifyRequest $request)
    {
        self::authorizeToCreate($request);

        $validator = self::validatorForStoring($request);

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

    /**
     * @param $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowToShow($request)
    {
        $this->authorizeToShow($request);
    }

    /**
     * @param $request
     * @param  Collection  $items
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowToViewAny($request, Collection $items)
    {
        $this->authorizeToViewAny($request);
    }
}
