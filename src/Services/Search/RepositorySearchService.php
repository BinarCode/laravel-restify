<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Events\AdvancedFiltersApplied;
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\EagerField;
use Binaryk\LaravelRestify\Filters\AdvancedFiltersCollection;
use Binaryk\LaravelRestify\Filters\Filter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Throwable;

class RepositorySearchService
{
    /** * @var Repository */
    protected $repository;

    public function search(RestifyRequest $request, Repository $repository): Builder|Relation
    {
        $this->repository = $repository;

        $scoutQuery = null;

        if ($repository::usesScout()) {
            $scoutQuery = $this->initializeQueryUsingScout($request, $repository);
        }

        $query = $this->prepareMatchFields(
            $request,
            $repository::usesScout()
                ? $this->prepareRelations($request, $scoutQuery ?? $repository::query($request))
                : $this->prepareSearchFields(
                    $request,
                    $this->prepareRelations($request, $scoutQuery ?? $repository::query($request)),
                ),
        );

        $query = $this->applyFilters($request, $repository, $query);

        $ordersBuilder = $this->prepareOrders($request, $query);

        return tap(
            tap($ordersBuilder, $this->applyMainQuery($request, $repository)),
            $this->applyIndexQuery($request, $repository)
        );
    }

    public function prepareMatchFields(RestifyRequest $request, $query)
    {
        $this->repository::collectMatches($request, $this->repository)->apply($request, $query);

        return $query;
    }

    /**
     * Resolve orders.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function prepareOrders(RestifyRequest $request, $query)
    {
        $collection = $this->repository::collectSorts($request, $this->repository);

        if ($collection->isEmpty()) {
            return empty($query->getQuery()->orders)
                ? $query->latest($query->getModel()->getQualifiedKeyName())
                : $query;
        }

        $collection->apply($request, $query);

        return $query;
    }

    public function prepareRelations(RestifyRequest $request, Builder|Relation $query)
    {
        $eager = ($this->repository)::collectRelated()
            ->forRequest($request, $this->repository)
            ->map(
                fn ($relation) => $relation instanceof EagerField
                    ? $relation->relation
                    : $relation
            )
            ->values()
            ->unique()
            ->all();

        if (empty($eager)) {
            return $query;
        }

        $filtered = collect($request->related()->makeTree())->filter(fn (string $relationships) => in_array(
            str($relationships)->whenContains('.', fn (Stringable $string) => $string->before('.'))->toString(),
            $eager,
            true,
        ))->filter(function ($relation) use ($query) {
            try {
                if ($relation === 'target') {
                    ray($query->getRelation($relation));
                    ray($query->getRelation($relation) instanceof Relation);
                }

                return $query->getRelation($relation) instanceof Relation;
            } catch (Throwable) {
                return false;
            }
        })->all();

        return $query->with(
            array_merge($filtered, ($this->repository)::withs())
        );
    }

    public function prepareSearchFields(RestifyRequest $request, $query)
    {
        $search = $request->input('search');

        if (empty($search)) {
            return $query;
        }

        $model = $query->getModel();

        $query->where(function ($query) use ($search, $model, $request) {
            $connectionType = $model->getConnection()->getDriverName();

            $canSearchPrimaryKey = is_numeric($search) &&
                in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                in_array($query->getModel()->getKeyName(), $this->repository::searchables());

            if ($canSearchPrimaryKey) {
                $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
            }

            foreach ($this->repository::searchables() as $key => $column) {
                $filter = $column instanceof Filter
                    ? $column
                    : SearchableFilter::make()->setColumn(
                        $model->qualifyColumn(is_numeric($key) ? $column : $key)
                    );

                $filter
                    ->setRepository($this->repository)
                    ->setColumn(
                        $filter->column ?? $model->qualifyColumn(is_numeric($key) ? $column : $key)
                    );

                $filter->filter($request, $query, $search);

                $this->repository::collectRelated()
                    ->onlySearchable($request)
                    ->map(function (BelongsTo $field) {
                        return SearchableFilter::make()->setRepository($this->repository)->usingBelongsTo($field);
                    })
                    ->each(fn (SearchableFilter $filter) => $filter->filter($request, $query, $search));
            }
        });

        return $query;
    }

    protected function applyIndexQuery(RestifyRequest $request, Repository $repository)
    {
        if ($request->isIndexRequest() || $request->isGlobalRequest()) {
            return fn ($query) => $repository::indexQuery($request, $query);
        }

        if ($request->isShowRequest()) {
            return fn ($query) => $repository::showQuery($request, $query);
        }

        return fn ($query) => $query;
    }

    public function initializeQueryUsingScout(RestifyRequest $request, Repository $repository): Builder
    {
        /**
         * @var Collection $keys
         */
        $keys = tap(
            is_null($request->input('search')) ? $repository::newModel() : $repository::newModel()->search($request->input('search')),
            function ($scoutBuilder) use ($repository, $request) {
                return $repository::scoutQuery($request, $scoutBuilder);
            }
        )->take($repository::$scoutSearchResults)->get()->map->getKey();

        return $repository::newModel()->newQuery()->whereIn(
            $repository::newModel()->getQualifiedKeyName(),
            $keys->all()
        );
    }

    protected function applyMainQuery(RestifyRequest $request, Repository $repository): callable
    {
        return fn ($query) => $repository::mainQuery($request, $query->with($repository::withs()));
    }

    protected function applyFilters(RestifyRequest $request, Repository $repository, $query)
    {
        event(
            new AdvancedFiltersApplied(
                $repository,
                AdvancedFiltersCollection::collectQueryFilters($request, $repository)
                    ->apply($request, $query),
                $request->input('filters'),
            )
        );

        return $query;
    }

    public static function make(): static
    {
        return new static();
    }
}
