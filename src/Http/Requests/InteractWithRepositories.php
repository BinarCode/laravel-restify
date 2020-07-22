<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithRepositories
{
    /**
     * @var Model
     */
    public $model;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the class name of the repository being requested.
     *
     * @param null $key
     * @return Repository
     */
    public function repository($key = null): ?Repository
    {
        $repository = tap(Restify::repositoryForKey($key ?? $this->route('repository')), function ($repository) {
            /** * @var Repository $repository */
            if (is_null($repository)) {
                throw new EntityNotFoundException(__('Repository :name not found.', [
                    'name' => $repository,
                ]), 404);
            }

            if (! $repository::authorizedToUseRepository($this)) {
                throw new UnauthorizedException(__('Unauthorized to view repository :name. Check "allowRestify" policy.', [
                    'name' => $repository,
                ]), 403);
            }

            if (! $repository::authorizedToUseRoute($this)) {
                abort(403, __('Unauthorized to use the route :name. Check prefix.', [
                    'name' => $this->getRequestUri(),
                ]));
            }

            app(Pipeline::class)
                ->send($this)
                ->through(optional($repository::collectMiddlewares($this))->toArray())
                ->thenReturn();
        });

        return $repository::resolveWith($repository::newModel());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    /**
     * Get the route handling the request.
     *
     * @param string|null $param
     * @param mixed $default
     * @return \Illuminate\Routing\Route|object|string
     */
    abstract public function route($param = null, $default = null);

    /**
     * Get a new instance of the repository being requested.
     *
     * @return Repository
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function newRepository()
    {
        $repository = $this->repository();

        return $repository::resolveWith($repository::newModel());
    }

    /**
     * Get a new instance of the repository being requested.
     * As a model it could accept either a model instance, a collection or even paginated collection.
     *
     * @param  $model
     * @param null $uriKey
     * @return Repository
     */
    public function newRepositoryWith($model, $uriKey = null)
    {
        $repository = $this->repository($uriKey);

        return $repository::resolveWith($model);
    }

    /**
     * Get a new, scopeless query builder for the underlying model.
     *
     * @param null $uriKey
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function newQueryWithoutScopes($uriKey = null)
    {
        if (! $this->isViaRepository()) {
            return $this->model($uriKey)->newQueryWithoutScopes();
        }

        return $this->viaQuery();
    }

    /**
     * Get a new instance of the underlying model.
     *
     * @param null $uriKey
     * @return \Illuminate\Database\Eloquent\Model
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function model($uriKey = null)
    {
        $repository = $this->repository($uriKey);

        return $repository::newModel();
    }

    /**
     * Get the query to find the model instance for the request.
     *
     * @param mixed|null $repositoryId
     * @param null $uriKey
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function findModelQuery($repositoryId = null, $uriKey = null)
    {
        return $this->newQueryWithoutScopes($uriKey)->whereKey(
            $repositoryId ?? request('repositoryId')
        );
    }

    public function findModelOrFail($id = null)
    {
        if ($id) {
            return $this->findModelQuery($id)->firstOrFail();
        }

        return once(function () {
            return $this->findModelQuery()->firstOrFail();
        });
    }

    public function findRelatedModelOrFail()
    {
        return once(function () {
            return $this->findRelatedQuery()->firstOrFail();
        });
    }

    public function findRelatedQuery($relatedRepository = null, $relatedRepositoryId = null)
    {
        return $this->repository($relatedRepository ?? request('relatedRepository'))::newModel()
            ->newQueryWithoutScopes()
            ->whereKey($relatedRepositoryId ?? request('relatedRepositoryId'));
    }

    public function viaParentModel()
    {
        $parent = $this->repository($this->viaRepository);

        return once(fn () => $parent::newModel()->newQueryWithoutScopes()->whereKey($this->viaRepositoryId)->firstOrFail());
    }

    public function viaQuery()
    {
        return $this->viaParentModel()->{$this->viaRelationship ?? request('repository')}();
    }

    /**
     * Get a new instance of the "related" resource being requested.
     *
     * @return Repository
     */
    public function newRelatedRepository()
    {
        $resource = $this->relatedRepository();

        return new $resource($resource::newModel());
    }

    public function relatedRepository()
    {
        return Restify::repositoryForKey($this->relatedRepository);
    }
}
