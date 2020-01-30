<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Controllers\RestResponse;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Http\Requests\RepositoryDestroyRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryStoreRequest;
use Binaryk\LaravelRestify\Http\Requests\RepositoryUpdateRequest;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\SearchService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait Crudable
{
    /**
     * @param RestifyRequest $request
     * @return JsonResponse
     * @throws \Binaryk\LaravelRestify\Exceptions\InstanceOfException
     * @throws \Throwable
     */
    public function index(RestifyRequest $request)
    {
        $results = SearchService::instance()->search($request, $this->model());

        $results = $results->tap(function ($query) use ($request) {
            static::indexQuery($request, $query);
        });

        /**
         * @var AbstractPaginator
         */
        $paginator = $results->paginate($request->get('perPage') ?? (static::$defaultPerPage ?? RestifySearchable::DEFAULT_PER_PAGE));

        $items = $paginator->getCollection()->map(function ($value) {
            return static::resolveWith($value);
        });

        try {
            $this->allowToShowEvery($request, $items);
        } catch (UnauthorizedException | AuthorizationException $e) {
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
     * @param RestifyRequest $request
     * @param $repositoryId
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public function show(RestifyRequest $request, $repositoryId)
    {
        $this->resource = static::showPlain($repositoryId);

        try {
            $this->allowToShow($request);
        } catch (AuthorizationException $e) {
            return $this->response()->forbidden()->addError($e->getMessage());
        }

        return $this->response()->data($this->jsonSerialize());
    }

    /**
     * @param RestifyRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(RestifyRequest $request)
    {
        try {
            $this->allowToStore($request);
        } catch (AuthorizationException | UnauthorizedException $e) {
            return $this->response()->addError($e->getMessage())->code(RestResponse::REST_RESPONSE_FORBIDDEN_CODE);
        } catch (ValidationException $e) {
            return $this->response()->addError($e->errors())
                ->code(RestResponse::REST_RESPONSE_INVALID_CODE);
        }

        $this->resource = static::storePlain($request->toArray());

        static::stored($this->resource);

        return $this->response('', RestResponse::REST_RESPONSE_CREATED_CODE)
            ->model($this->resource)
            ->header('Location', Restify::path().'/'.static::uriKey().'/'.$this->resource->id);
    }

    /**
     * @param RestifyRequest $request
     * @param $repositoryId
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws ValidationException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public function update(RestifyRequest $request, $repositoryId)
    {
        $this->allowToUpdate($request);

        $this->resource = static::updatePlain($request->all(), $repositoryId);

        static::updated($this->resource);

        return $this->response()
            ->data($this->jsonSerialize())
            ->updated();
    }

    /**
     * @param RestifyRequest $request
     * @param $repositoryId
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public function destroy(RestifyRequest $request, $repositoryId)
    {
        $this->allowToDestroy($request);

        $status = static::destroyPlain($repositoryId);

        static::deleted($status);

        return $this->response()->deleted();
    }

    /**
     * @param RestifyRequest $request
     * @param array $payload
     * @return mixed
     */
    public function allowToUpdate(RestifyRequest $request, $payload = null)
    {
        $this->authorizeToUpdate($request);

        $validator = static::validatorForUpdate($request, $this, $payload);

        $validator->validate();
    }

    /**
     * @param RestifyRequest $request
     * @param array $payload
     * @return mixed
     */
    public function allowToStore(RestifyRequest $request, $payload = null)
    {
        static::authorizeToStore($request);

        $validator = static::validatorForStoring($request, $payload);

        $validator->validate();
    }

    /**
     * @param RestifyRequest $request
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
     * @param Collection $items
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function allowToShowEvery($request, Collection $items)
    {
        $this->authorizeToShowEvery($request);
    }

    /**
     * Validate input array and store a new entity.
     *
     * @param array $payload
     * @return mixed
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public static function storePlain(array $payload)
    {
        /** * @var RepositoryStoreRequest $request */
        $request = resolve(RepositoryStoreRequest::class);

        $request->attributes->add($payload);

        $repository = resolve(static::class);

        $repository->allowToStore($request, $payload);

        return DB::transaction(function () use ($request) {
            $model = static::fillWhenStore(
                $request, static::newModel()
            );

            $model->save();

            return $model;
        });
    }

    /**
     * Update an entity with an array of payload.
     *
     * @param array $payload
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws ValidationException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public static function updatePlain(array $payload, $id)
    {
        /** * @var RepositoryUpdateRequest $request */
        $request = resolve(RepositoryUpdateRequest::class);
        $request->attributes->add($payload);

        $model = $request->findModelQuery($id, static::uriKey())->lockForUpdate()->firstOrFail();

        /**
         * @var Repository
         */
        $repository = $request->newRepositoryWith($model, static::uriKey());

        $repository->allowToUpdate($request, $payload);

        return DB::transaction(function () use ($request, $repository) {
            $model = static::fillWhenUpdate($request, $repository->resource);

            $model->save();

            return $model;
        });
    }

    /**
     * Returns a plain model by key
     * Used as: Book::showPlain(1).
     *
     * @param $key
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Model
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public static function showPlain($key)
    {
        /** * @var RestifyRequest $request */
        $request = resolve(RestifyRequest::class);

        /**
         * Dive into the Search service to attach relations.
         */
        $repository = $request->newRepositoryWith(tap($request->findModelQuery($key, static::uriKey())->firstOrFail(), function ($query) use ($request) {
            static::detailQuery($request, $query);
        }));

        $repository->allowToShow($request);

        return $repository->resource;
    }

    /**
     * Validate deletion and delete entity.
     *
     * @param $key
     * @return mixed
     * @throws AuthorizationException
     * @throws UnauthorizedException
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
     */
    public static function destroyPlain($key)
    {
        /** * @var RepositoryDestroyRequest $request */
        $request = resolve(RepositoryDestroyRequest::class);

        $repository = $request->newRepositoryWith($request->findModelQuery($key, static::uriKey())->firstOrFail(), static::uriKey());

        $repository->allowToDestroy($request);

        return DB::transaction(function () use ($repository) {
            return $repository->resource->delete();
        });
    }

    /**
     * @param $model
     */
    public static function stored($model)
    {
        //
    }

    /**
     * @param $model
     */
    public static function updated($model)
    {
        //
    }

    /**
     * @param int $status
     */
    public static function deleted($status)
    {
        //
    }
}
