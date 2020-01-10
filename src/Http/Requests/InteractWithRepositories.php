<?php

namespace Binaryk\LaravelRestify\Http\Requests;

use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;
use Binaryk\LaravelRestify\Exceptions\UnauthorizedException;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Model;

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
     * @return Repository
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function repository()
    {
        return tap(Restify::repositoryForKey($this->route('repository')), function ($repository) {
            if (is_null($repository)) {
                throw new EntityNotFoundException(__('Repository :name not found.', [
                    'name' => $repository,
                ]), 404);
            }

            if (! $repository::authorizedToViewAny($this)) {
                throw new UnauthorizedException(__('Unauthorized to view repository :name.', [
                    'name' => $repository,
                ]), 403);
            }
        });
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
     * @param  string|null  $param
     * @param  mixed  $default
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
     * Check if the route is resolved by the Repository class, or it uses the classical Models.
     * @return bool
     */
    public function isResolvedByRestify()
    {
        try {
            $this->repository();

            return true;
        } catch (EntityNotFoundException $e) {
            return false;
        } catch (UnauthorizedException $e) {
            return true;
        }
    }

    /**
     * Get a new instance of the repository being requested.
     * As a model it could accept either a model instance, a collection or even paginated collection.
     *
     * @param  $model
     * @return Repository
     */
    public function newRepositoryWith($model)
    {
        $repository = $this->repository();

        return resolve($repository, [
            'model' => $model,
        ])->withResource($model);
    }

    /**
     * Get a new, scopeless query builder for the underlying model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function newQueryWithoutScopes()
    {
        return $this->model()->newQueryWithoutScopes();
    }

    /**
     * Get a new instance of the underlying model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws EntityNotFoundException
     * @throws UnauthorizedException
     */
    public function model()
    {
        $repository = $this->repository();

        return $repository::newModel();
    }

    /**
     * Get the query to find the model instance for the request.
     *
     * @param  mixed|null  $repositoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findModelQuery($repositoryId = null)
    {
        return $this->newQueryWithoutScopes()->whereKey(
            $repositoryId ?? request('repositoryId')
        );
    }
}
