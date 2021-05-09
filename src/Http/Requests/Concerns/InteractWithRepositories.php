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

    public function newRepository(): Repository
    {
        $repository = $this->repository();

        return $repository::resolveWith($repository::newModel());
    }

    public function newRelatedRepository(): Repository
    {
        $repository = $this->repository(
            $this->route('relatedRepository')
        );

        return $repository::resolveWith($repository::newModel());
    }

    /**
     * Get a new instance of the repository being requested.
     * As a model it could accept either a model instance, a collection or even paginated collection.
     *
     * @param  $model
     * @param  null  $uriKey
     * @return Repository
     */
    public function newRepositoryWith($model, $uriKey = null): Repository
    {
        $repository = $this->repository($uriKey);

        return $repository::resolveWith($model);
    }

    public function newQuery($uriKey = null): Builder|Relation
    {
        if (!$this->isViaRepository()) {
            return $this->model($uriKey)->newQuery();
        }

        return $this->scopedViaParentModel();
    }

    public function model($uriKey = null): Model
    {
        $repository = $this->repository($uriKey);

        return $repository::newModel();
    }

    public function findModelQuery(string $repositoryId = null, string $uriKey = null): Builder|Relation
    {
        return $this->newQuery($uriKey)->whereKey(
            $repositoryId ?? $this->route('repositoryId')
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
        return $this->repository($relatedRepository ?? $this->route('relatedRepository'))::newModel()
            ->newQueryWithoutScopes()
            ->whereKey($relatedRepositoryId ?? request('relatedRepositoryId'));
    }

    public function scopedViaParentModel(): Relation
    {
        return $this->relatedEagerField()->getRelation();
    }

    public function viaQuery(): Relation
    {
        return with($this->relatedEagerField(), fn(EagerField $field) => $field->getRelation(
            $field->parentRepository
        ));
    }

    public function relatedRepository(): ?string
    {
        return Restify::repositoryForKey($this->route('relatedRepository'));
    }
}
