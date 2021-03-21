<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;

class RepositorySearchService extends Searchable
{
    /**
     * @var Repository
     */
    protected $repository;

    public function search(RestifyRequest $request, Repository $repository)
    {
        $this->repository = $repository;

        $query = $this->prepareMatchFields(
            $request,
            $this->prepareSearchFields($request, $this->prepareRelations($request, $repository::query($request)), $this->fixedInput),
            $this->fixedInput);

        $query = $this->applyFilters($request, $repository, $query);

        return tap(
            tap($this->prepareOrders($request, $query), $this->applyMainQuery($request, $repository)), $this->applyIndexQuery($request, $repository)
        );
    }

    public function prepareMatchFields(RestifyRequest $request, $query, $extra = [])
    {
        $this->repository::collectMatches($request, $this->repository)->apply($request, $query);

        return $query;
    }

    /**
     * Resolve orders.
     *
     * @param RestifyRequest $request
     * @param Builder $query
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

    public function prepareRelations(RestifyRequest $request, $query)
    {
        return $query->with($this->repository->getWiths());
    }

    public function prepareSearchFields(RestifyRequest $request, $query, $extra = [])
    {
        $search = $request->input('search', data_get($extra, 'search', ''));

        if (empty($search)) {
            return $query;
        }

        $model = $query->getModel();

        $query->where(function ($query) use ($search, $model, $request) {
            $connectionType = $model->getConnection()->getDriverName();

            $canSearchPrimaryKey = is_numeric($search) &&
                in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                in_array($query->getModel()->getKeyName(), $this->repository->getSearchableFields());

            if ($canSearchPrimaryKey) {
                $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
            }

            foreach ($this->repository->getSearchableFields() as $key => $column) {
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
        return fn ($query) => $repository::indexQuery($request, $query);
    }

    protected function applyMainQuery(RestifyRequest $request, Repository $repository)
    {
        return fn ($query) => $repository::mainQuery($request, $query->with($repository::getWiths()));
    }

    protected function applyFilters(RestifyRequest $request, Repository $repository, $query)
    {
        $repository
            ->collectAdvancedFilters($request)
            ->inQuery($request)
            ->resolve($request)
            ->apply($request, $query);

        return $query;
    }
}
