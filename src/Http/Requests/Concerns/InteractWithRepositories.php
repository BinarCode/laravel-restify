<?php

namespace Binaryk\LaravelRestify\Http\Requests\Concerns;

use Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pipeline\Pipeline;

/**
 * @mixin RestifyRequest
 */
trait InteractWithRepositories
{
    public function repository($key = null): Repository
    {
        $repository = tap(Restify::repositoryForKey($key ?? $this->route('repository')), function (string $repository) {
            /** * @var Repository $repository */
            if (is_null($repository)) {
                throw RepositoryNotFoundException::make(__('Repository :name not found.', [
                    'name' => $repository,
                ]));
            }

            if (!$repository::authorizedToUseRepository($this)) {
                abort(403, __(
                    'Unauthorized to view repository :name. Check "allowRestify" policy.',
                    [
                        'name' => $repository,
                    ]
                ));
            }

            if (!$repository::authorizedToUseRoute($this)) {
                abort(403, __('Unauthorized to use the route :name. Check prefix.', [
                    'name' => $this->getRequestUri(),
                ]));
            }

            app(Pipeline::class)
                ->send($this)
                ->through(optional($repository::collectMiddlewares($this))->all())
                ->thenReturn();
        });

        return $repository::isMock()
            ? $repository::getMock()::resolveWith($repository::newModel())
            : $repository::resolveWith($repository::newModel());
    }

    public function repositoryWith($model, $uriKey = null): Repository
    {
        $repository = $this->repository($uriKey);

        return $repository::resolveWith($model);
    }

    public function model($uriKey = null): Model
    {
        $repository = $this->repository($uriKey);

        return $repository::newModel();
    }

    public function newQuery($uriKey = null): Builder|Relation
    {
        if (!$this->isViaRepository()) {
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
        $matchSomePrefixes = collect(Restify::$repositories)
                ->some(fn($repository) => $repository::prefix() === "$parentRepository/$parentRepositoryId")
            || collect(Restify::$repositories)->some(fn(
                $repository
            ) => $repository::indexPrefix() === "$parentRepository/$parentRepositoryId");

        if ($matchSomePrefixes) {
            return false;
        }

        return $parentRepository && $parentRepositoryId;
    }
}
