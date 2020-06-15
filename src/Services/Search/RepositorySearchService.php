<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Filter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;

class RepositorySearchService extends Searchable
{
    protected Repository $repository;

    public function search(RestifyRequest $request, Repository $repository)
    {
        $this->repository = $repository;

        $query = $this->prepareMatchFields($request, $this->prepareSearchFields($request, $repository::query($request), $this->fixedInput), $this->fixedInput);

        $query = $this->applyFilters($request, $repository, $query);

        return tap($this->prepareRelations($request, $this->prepareOrders($request, $query), $this->fixedInput), $this->applyIndexQuery($request, $repository));
    }

    public function prepareMatchFields(RestifyRequest $request, $query, $extra = [])
    {
        $model = $query->getModel();
        foreach ($this->repository->getMatchByFields() as $key => $type) {
            if (! $request->has($key) && ! data_get($extra, "match.$key")) {
                continue;
            }

            $value = $request->get($key, data_get($extra, "match.$key"));

            if (empty($value)) {
                continue;
            }

            $field = $model->qualifyColumn($key);

            $values = explode(',', $value);

            foreach ($values as $match) {
                switch ($this->repository->getMatchByFields()[$key]) {
                    case RestifySearchable::MATCH_TEXT:
                    case 'string':
                        $query->where($field, '=', $match);
                        break;
                    case RestifySearchable::MATCH_BOOL:
                    case 'boolean':
                        if ($match === 'false') {
                            $query->where(function ($query) use ($field) {
                                return $query->where($field, '=', false)->orWhereNull($field);
                            });
                            break;
                        }
                        $query->where($field, '=', true);
                        break;
                    case RestifySearchable::MATCH_INTEGER:
                    case 'number':
                    case 'int':
                        $query->where($field, '=', (int) $match);
                        break;
                }
            }
        }

        return $query;
    }

    public function prepareOrders(RestifyRequest $request, $query, $extra = [])
    {
        $sort = $request->get('sort', '');

        if (isset($extra['sort'])) {
            $sort = $extra['sort'];
        }

        $params = explode(',', $sort);

        if (is_array($params) === true && empty($params) === false) {
            foreach ($params as $param) {
                $this->setOrder($query, $param);
            }
        }

        if (empty($params) === true) {
            $this->setOrder($query, '+id');
        }

        return $query;
    }

    public function prepareRelations(RestifyRequest $request, $query, $extra = [])
    {
        $relations = array_merge($extra, explode(',', $request->get('related')));

        foreach ($relations as $relation) {
            if (in_array($relation, $this->repository->getWiths())) {
                $query->with($relation);
            }
        }

        return $query;
    }

    public function prepareSearchFields(RestifyRequest $request, $query, $extra = [])
    {
        $search = $request->get('search', data_get($extra, 'search', ''));

        if (empty($search)) {
            return $query;
        }

        $model = $query->getModel();

        $query->where(function ($query) use ($search, $model) {
            $connectionType = $model->getConnection()->getDriverName();

            $canSearchPrimaryKey = is_numeric($search) &&
                in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                in_array($query->getModel()->getKeyName(), $this->repository->getSearchableFields());

            if ($canSearchPrimaryKey) {
                $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
            }

            $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

            foreach ($this->repository->getSearchableFields() as $column) {
                $query->orWhere($model->qualifyColumn($column), $likeOperator, '%'.$search.'%');
            }
        });

        return $query;
    }

    public function setOrder($query, $param)
    {
        if ($param === 'random') {
            $query->inRandomOrder();

            return $query;
        }

        $order = substr($param, 0, 1);

        if ($order === '-') {
            $field = substr($param, 1);
        }

        if ($order === '+') {
            $field = substr($param, 1);
        }

        if ($order !== '-' && $order !== '+') {
            $order = '+';
            $field = $param;
        }

        if (isset($field)) {
            if (in_array($field, $this->repository->getOrderByFields()) === true) {
                if ($order === '-') {
                    $query->orderBy($field, 'desc');
                }

                if ($order === '+') {
                    $query->orderBy($field, 'asc');
                }
            }

            if ($field === 'random') {
                $query->orderByRaw('RAND()');
            }
        }

        return $query;
    }

    protected function applyIndexQuery(RestifyRequest $request, Repository $repository)
    {
        return fn ($query) => $repository::indexQuery($request, $query);
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

                    if ($matchingFilter->invalidPayloadValue($request, $filter['value'])) {
                        return false;
                    }

                    $matchingFilter->resolve($request, $filter['value']);

                    return $matchingFilter;
                })
                ->filter()
                ->each(fn (Filter $filter) => $filter->filter($request, $query, $filter->value));
        }

        return $query;
    }
}
