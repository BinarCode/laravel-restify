<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class SearchService extends Searchable
{
    /**
     * @var Builder|QueryBuilder
     */
    protected $builder;

    /**
     * @param  RestifyRequest  $request
     * @param  Model  $model
     * @return Builder
     * @throws InstanceOfException
     */
    public function search(RestifyRequest $request, Model $model)
    {
        $this->request = $request;
        $this->model = $model;

        $this->builder = $model->newQuery();

        $this->prepare();

        return $this->builder;
    }

    /**
     * Will prepare the eloquent array to return
     *
     * @return array
     * @throws InstanceOfException
     */
    protected function prepare()
    {
        if ($this->model instanceof RestifySearchable) {
            $this->prepareSearchFields($this->request->get('search', data_get($this->fixedInput, 'search', '')))
                ->prepareMatchFields()
                ->prepareOperator($this->request->get('operator', []))
                ->prepareOrders($this->request->get('sort', ''))
                ->prepareRelations();
        } else {
            throw new InstanceOfException(__("Model is not an instance of :parent class", [
                'parent' => RestifySearchable::class,
            ]));
        }

        $results = $this->builder->get();

        return [
            'data' => $results,
            'aggregations' => null,
        ];
    }

    /**
     * Prepare eloquent exact fields
     *
     * @param $fields
     *
     * @return $this
     */
    protected function prepareOperator($fields)
    {
        if (isset($this->fixedInput['operator']) === true) {
            $fields = $this->fixedInput['operator'];
        }

        if (is_array($fields) === true) {
            foreach ($fields as $key => $values) {
                foreach ($values as $field => $value) {
                    $qualifiedField = $this->model->qualifyColumn($field);
                    switch ($key) {
                        case "gte":
                            $this->builder->where($qualifiedField, '>=', $value);
                            break;
                        case "gt":
                            $this->builder->where($qualifiedField, '>', $value);
                            break;
                        case "lte":
                            $this->builder->where($qualifiedField, '<=', $value);
                            break;
                        case "lt":
                            $this->builder->where($qualifiedField, '<', $value);
                            break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Prepare eloquent exact fields
     *
     * @param $fields
     *
     * @return $this
     */
    protected function prepareMatchFields()
    {
        foreach($this->model::getMatchByFields() as $key => $type) {
            if (! $this->request->has($key) && ! data_get($this->fixedInput, "match.$key")) {
                continue;
            }

            $value = $this->request->get($key) ?: data_get($this->fixedInput, "match.$key");

            $field = $this->model->qualifyColumn($key);

            $values = explode(',', $value);

            foreach ($values as $match) {
                switch ($this->model::getMatchByFields()[$key]) {
                    case RestifySearchable::MATCH_TEXT:
                    case 'string':
                        $this->builder->where($field, '=', $match);
                        break;
                    case RestifySearchable::MATCH_BOOL:
                    case 'boolean':
                        if ($match === 'false') {
                            $this->builder->where(function ($query) use ($field) {
                                return $query->where($field, '=', false)->orWhereNull($field);
                            });
                            break;
                        }
                        $this->builder->where($field, '=', true);
                        break;
                    case RestifySearchable::MATCH_INTEGER:
                    case 'number':
                    case 'int':
                        $this->builder->where($field, '=', (int) $match);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * Prepare eloquent order by
     *
     * @param $sort
     *
     * @return $this
     */
    protected function prepareOrders($sort)
    {
        if (isset($this->fixedInput['sort'])) {
            $sort = $this->fixedInput['sort'];
        }

        $params = explode(',', $sort);

        if (is_array($params) === true && empty($params) === false) {
            foreach ($params as $param) {
                $this->setOrder($param);
            }
        }

        if (empty($params) === true) {
            $this->setOrder('+id');
        }

        return $this;
    }

    /**
     * Prepare relations
     *
     * @return $this
     */
    protected function prepareRelations()
    {
        $relations = null;

        if (isset($this->fixedInput['with']) === true) {
            $relations = $this->fixedInput['with'];
        }

        if (isset($this->fixedInput['with']) === false) {
            $relations = $this->request->get('with', null);
        }

        if (empty($relations) === false) {
            $foundRelations = explode(',', $relations);
            foreach ($foundRelations as $relation) {
                if (in_array($relation, $this->model->getWiths())) {
                    $this->builder->with($relation);
                }
            }
        }

        return $this;
    }

    /**
     * Prepare search
     *
     * @param $search
     * @return $this
     */
    protected function prepareSearchFields($search)
    {
        $this->builder->where(function (Builder $query) use ($search) {
            $connectionType = $this->model->getConnection()->getDriverName();

            $canSearchPrimaryKey = is_numeric($search) &&
                in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                in_array($query->getModel()->getKeyName(), $this->model::getSearchableFields());


            if ($canSearchPrimaryKey) {
                $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
            }

            $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

            foreach ($this->model::getSearchableFields() as $column) {
                $query->orWhere($this->model->qualifyColumn($column), $likeOperator, '%' . $search . '%');
            }
        });

        return $this;
    }

    /**
     * Set order
     *
     * @param $param
     *
     * @return $this
     */
    public function setOrder($param)
    {
        if ($param === 'random') {
            $this->builder->inRandomOrder();
            return $this;
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

        if (in_array($field, $this->model::getOrderByFields()) === true) {
            if ($order === '-') {
                $this->builder->orderBy($field, 'desc');
            }

            if ($order === '+') {
                $this->builder->orderBy($field, 'asc');
            }
        }

        if ($field === 'random') {
            $this->builder->orderByRaw('RAND()');
        }

        return $this;
    }
}
