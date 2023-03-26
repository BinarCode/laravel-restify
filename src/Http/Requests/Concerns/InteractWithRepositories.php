<?php

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Exceptions\RepositoryException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\RepositoryInstance;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pipeline\Pipeline;
use Throwable;

/**
 * @mixin RestifyRequest
 */
trait InteractWithRepositories
{
    /**
     * @throws Throwable
     */
    public function repository($key = null): Repository
    {
        try {
            $key = $key ?? $this->route('repository');

            /**
             * @var Repository|null $class
             */
            if (is_null($key) && $class = Restify::repositoryClassForPrefix($this->getRequestUri())) {
                $key = $class::uriKey();
            }

            throw_if(is_null($key), RepositoryException::missingKey());

            $repository = Restify::repository($key);

            throw_unless(
                $repository::authorizedToUseRepository($this),
                RepositoryException::unauthorized($repository::uriKey())
            );

            throw_unless(
                $repository::authorizedToUseRoute($this),
                RepositoryException::routeUnauthorized($this->getRequestUri())
            );

            app(Pipeline::class)
                ->send($this)
                ->through(optional($repository::collectMiddlewares($this))->all())
                ->thenReturn();

            app()->singleton(RepositoryInstance::class, fn ($app) => new RepositoryInstance($repository));

            return $repository;
        } catch (RepositoryException $e) {
            abort($e->getCode() ?: 400, $e->getMessage());
        }
    }

    public function repositoryWith(Model $model, string $uriKey = null): Repository
    {
        $repository = $this->repository($uriKey);

        return $repository::resolveWith($model);
    }

    public function model(string $uriKey = null): Model
    {
        $repository = $this->repository($uriKey);

        return $repository::newModel();
    }

    public function newQuery(string $uriKey = null): Builder|Relation
    {
        if (! $this->isViaRepository()) {
            return $this->model($uriKey)->newQuery();
        }

        return $this->viaQuery();
    }

    public function viaQuery(): Relation
    {
        return $this->relatedEagerField()->getRelation();
    }

    public function modelQuery(string $repositoryId = null, string $uriKey = null): Builder|Relation
    {
        return $this->newQuery($uriKey)->whereKey(
            $repositoryId ?? $this->route('repositoryId')
        );
    }

    public function findModelOrFail($id = null): Model
    {
        return $id
            ? $this->modelQuery($id)->firstOrFail()
            : once(function () {
                return $this->modelQuery()->firstOrFail();
            });
    }

    public function isViaRepository(): bool
    {
        $parentRepository = $this->route('parentRepository');
        $parentRepositoryId = $this->route('parentRepositoryId');

        //TODO: Find another implementation for prefixes:
        $matchSomePrefixes = collect(Restify::$repositories)->some(fn (
            $repository
        ) => $repository::prefix() === "$parentRepository/$parentRepositoryId");

        if ($matchSomePrefixes) {
            return false;
        }

        return $parentRepository && $parentRepositoryId;
    }
}
