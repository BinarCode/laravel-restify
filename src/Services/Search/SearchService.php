<?php

namespace Binaryk\LaravelRestify\Services\Search;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Exceptions\InstanceOfException;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SearchService extends Searchable
{
    /**
     * @param  RestifyRequest  $request
     * @param  Model  $model
     * @return Builder
     * @throws InstanceOfException
     * @throws \Throwable
     */
    public function search(RestifyRequest $request, Model $model)
    {
        throw_unless($model instanceof RestifySearchable, new InstanceOfException(__('Model is not an instance of :parent class', [
            'parent' => RestifySearchable::class,
        ])));

        $query = $this->prepareMatchFields($request, $this->prepareSearchFields($request, $model->newQuery(), $this->fixedInput), $this->fixedInput);

        return $this->prepareRelations($request, $this->prepareOrders($request, $query), $this->fixedInput);
    }

    /**
     * Prepare eloquent exact fields.
     *
     * @param  RestifyRequest  $request
     * @param  Builder  $query
     * @param  array  $extra
     * @return Builder
     */
    public function prepareMatchFields(RestifyRequest $request, $query, $extra = [])
    {
        $model = $query->getModel();
        if ($model instanceof RestifySearchable) {
            foreach ($model::getMatchByFields() as $key => $type) {
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
                    switch ($model::getMatchByFields()[$key]) {
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
        }

        return $query;
    }

    /**
     * Prepare eloquent order by.
     *
     * @param  RestifyRequest  $request
     * @param $query
     * @param  array  $extra
     * @return Builder
     */
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

    /**
     * Prepare relations.
     *
     * @param  RestifyRequest  $request
     * @param  Builder  $query
     * @param  array  $extra
     * @return Builder
     */
    public function prepareRelations(RestifyRequest $request, $query, $extra = [])
    {
        $model = $query->getModel();
        if ($model instanceof RestifySearchable) {
            $relations = array_merge($extra, explode(',', $request->get('with')));
            foreach ($relations as $relation) {
                if (in_array($relation, $model::getWiths())) {
                    $query->with($relation);
                }
            }
        }

        return $query;
    }

    /**
     * Prepare search.
     *
     * @param  RestifyRequest  $request
     * @param  Builder  $query
     * @param  array  $extra
     * @return Builder
     */
    public function prepareSearchFields(RestifyRequest $request, $query, $extra = [])
    {
        $search = $request->get('search', data_get($extra, 'search', ''));
        $model = $query->getModel();
        if ($model instanceof RestifySearchable) {
            $query->where(function (Builder $query) use ($search, $model) {
                $connectionType = $model->getConnection()->getDriverName();

                $canSearchPrimaryKey = is_numeric($search) &&
                    in_array($query->getModel()->getKeyType(), ['int', 'integer']) &&
                    ($connectionType != 'pgsql' || $search <= PHP_INT_MAX) &&
                    in_array($query->getModel()->getKeyName(), $model::getSearchableFields());

                if ($canSearchPrimaryKey) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $search);
                }

                $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

                foreach ($model::getSearchableFields() as $column) {
                    $query->orWhere($model->qualifyColumn($column), $likeOperator, '%'.$search.'%');
                }
            });
        }

        return $query;
    }

    /**
     * @param  $query
     * @param $param
     * @return Builder
     */
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

        $model = $query->getModel();

        if (isset($field) && $model instanceof RestifySearchable) {
            if (in_array($field, $model::getOrderByFields()) === true) {
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
}
