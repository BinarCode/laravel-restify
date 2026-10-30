<?php

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Exceptions\RepositoryException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Repositories\RepositoryInstance;
use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Services\Search\RepositorySearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
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
            $key = $key ?? $this->route('repository') ?? $this->input('mcp_repository_key');

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
            abort($e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function repositoryWith(Model $model, ?string $uriKey = null): Repository
    {
        $repository = $this->repository($uriKey);

        return $repository::resolveWith($model);
    }

    public function model(?string $uriKey = null): Model
    {
        $repository = $this->repository($uriKey);

        return $repository::newModel();
    }

    public function newQuery(?string $uriKey = null): Builder|Relation
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

    public function modelQuery(?string $repositoryId = null, ?string $uriKey = null): Builder|Relation
    {
        return $this->newQuery($uriKey)->where(
            $this->model($uriKey)->getRouteKeyName(),
            $repositoryId ?? $this->route('repositoryId')
        );
    }

    /**
     * The model query scoped by the repository mainQuery, as show and index requests are.
     *
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function scopedModelQuery(?string $repositoryId = null, ?string $uriKey = null): Builder|Relation
    {
        $query = $this->modelQuery($repositoryId, $uriKey);

        if (! config('restify.repositories.apply_main_query_to_actions_and_getters', true)) {
            return $query;
        }

        return $this->repository($uriKey)::mainQuery($this, $query);
    }

    /**
     * @param  list<int|string>  $repositoryIds
     */
    public function modelsQuery(array $repositoryIds, ?string $uriKey = null): Builder|Relation
    {
        return $this->newQuery($uriKey)->whereIn(
            $this->model($uriKey)->getRouteKeyName(),
            $repositoryIds
        );
    }

    /**
     * The model query for an update, patch or delete, scoped by the repository mainQuery.
     *
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function writableModelQuery(?string $repositoryId = null, ?string $uriKey = null): Builder|Relation
    {
        return $this->scopeForWrite($this->modelQuery($repositoryId, $uriKey), $uriKey);
    }

    /**
     * @param  list<int|string>  $repositoryIds
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    public function writableModelsQuery(array $repositoryIds, ?string $uriKey = null): Builder|Relation
    {
        return $this->scopeForWrite($this->modelsQuery($repositoryIds, $uriKey), $uriKey);
    }

    /**
     * @param  Builder<Model>|Relation<Model, Model, mixed>  $query
     * @return Builder<Model>|Relation<Model, Model, mixed>
     */
    private function scopeForWrite(Builder|Relation $query, ?string $uriKey): Builder|Relation
    {
        if (! config('restify.repositories.apply_main_query_to_writes', true)) {
            return $query;
        }

        return $this->repository($uriKey)::mainQuery($this, $query);
    }

    public function findModelOrFail($id = null, ?string $uriKey = null): Model
    {
        return $id
            ? $this->modelQuery($id, $uriKey)->firstOrFail()
            : once(function () use ($uriKey) {
                return $this->modelQuery(uriKey: $uriKey)->firstOrFail();
            });
    }

    public function isViaRepository(): bool
    {
        $parentRepository = $this->route('parentRepository');
        $parentRepositoryId = $this->route('parentRepositoryId');

        // TODO: Find another implementation for prefixes:
        $matchSomePrefixes = collect(Restify::$repositories)->some(fn (
            $repository
        ) => $repository::prefix() === "$parentRepository/$parentRepositoryId");

        if ($matchSomePrefixes) {
            return false;
        }

        return $parentRepository && $parentRepositoryId;
    }

    public function filteredQuery(): Builder
    {
        return RepositorySearchService::make()->search($this, $this->repository());
    }
}
