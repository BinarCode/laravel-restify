<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Filters\SortableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Matchable;
use Binaryk\LaravelRestify\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class RepositorySearchService extends Searchable
{
    /**
     * @var Repository
     */
    protected $repository;

    public function search(RestifyRequest $request, Repository $repository)
    {
        $this->repository = $repository;

        $query = $this->prepareMatchFields($request, $this->prepareSearchFields($request, $repository::query($request), $this->fixedInput), $this->fixedInput);

        $query = $this->applyFilters($request, $repository, $query);

        return tap(
            tap($this->prepareOrders($request, $query), $this->applyMainQuery($request, $repository)), $this->applyIndexQuery($request, $repository)
        );
    }

    public function prepareMatchFields(RestifyRequest $request, $query, $extra = [])
    {
        /** * @var Builder $query */
        $model = $query->getModel();
        foreach ($this->repository->getMatchByFields() as $key => $type) {
            $negation = false;

            if ($request->has('-'.$key)) {
                $negation = true;
            }

            if (! $request->has($negation ? '-'.$key : $key) && ! data_get($extra, "match.$key")) {
                continue;
            }

            $match = $request->input($negation ? '-'.$key : $key, data_get($extra, "match.$key"));

            if ($negation) {
                $key = Str::after($key, '-');
            }

            if (empty($match)) {
                continue;
            }

            if (is_callable($type)) {
                call_user_func_array($type, [
                    $request, $query,
                ]);

                continue;
            }

            if (is_subclass_of($type, Matchable::class)) {
                call_user_func_array([
                    app($type), 'handle',
                ], [
                    $request, $query,
                ]);

                continue;
            }

            $filter = $type instanceof Filter
                ? $type
                : MatchFilter::make()->setType($type);

            $filter->setRepository($this->repository)
                ->setColumn(
                    $filter->column ?? $model->qualifyColumn($key)
                );

            call_user_func_array([
                $filter, 'filter',
            ], [
                $request, $query, $match,
            ]);
        }

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

        $collection->each(function (SortableFilter $filter) use ($request, $query) {
            $filter->filter($request, $query, $filter->direction());
        });

        return $query;
    }

    public function prepareRelations(RestifyRequest $request, $query, $extra = [])
    {
        $relations = array_merge($extra, explode(',', $request->input('related')));

        foreach ($relations as $relation) {
            if (in_array($relation, $this->repository->getWiths())) {
                $query->with($relation);
            }
        }

        return $query;
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
        if (! empty($request->filters)) {
            $filters = json_decode(base64_decode($request->filters), true);

            collect($filters)
                ->map(function ($filter) use ($request, $repository) {
                    /** * @var Filter $matchingFilter */
                    $matchingFilter = $repository->availableFilters($request)->first(function ($availableFilter) use ($filter) {
                        return $filter['class'] === $availableFilter->key();
                    });

                    if (is_null($matchingFilter)) {
                        return false;
                    }

                    if (array_key_exists('value', $filter) && $matchingFilter->invalidPayloadValue($request, $filter['value'])) {
                        return false;
                    }

                    $matchingFilter->resolve(
                        $request,
                        array_key_exists('value', $filter) ? $filter['value'] : null
                    );

                    return $matchingFilter;
                })
                ->filter()
                ->each(fn (Filter $filter) => $filter->filter($request, $query, $filter->value));
        }

        return $query;
    }
}
