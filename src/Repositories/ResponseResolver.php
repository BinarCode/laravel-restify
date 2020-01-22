<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Restify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait ResponseResolver
{
    /**
     * Return the attributes list.
     *
     * Resolve all model fields through showCallback methods and exclude from the final response if
     * that is required by method
     *
     * @param $request
     * @return array
     */
    public function resolveDetailsAttributes(RestifyRequest $request)
    {
        $resolvedAttributes = [];
        $modelAttributes = method_exists($this->resource, 'toArray') ? $this->resource->toArray($request) : [];
        $this->collectFields($request)->filter(function (Field $field) {
            return is_callable($field->showCallback);
        })->map(function (Field $field) use (&$resolvedAttributes) {
            $resolvedAttributes[$field->attribute] = $field->resolveForShow($this);
        });

        $resolved = array_merge($modelAttributes, $resolvedAttributes);

        if ($request->isDetailRequest()) {
            $hidden = $this->collectFields($request)->filter->isHiddenOnDetail($request, $this)->pluck('attribute')->toArray();

            $resolved = Arr::except($resolved, $hidden);
        }

        if ($request->isIndexRequest()) {
            $hidden = $this->collectFields($request)->filter->isHiddenOnIndex($request, $this)->pluck('attribute')->toArray();

            $resolved = Arr::except($resolved, $hidden);
        }

        return $resolved;
    }

    /**
     * @param $request
     * @return array
     */
    public function resolveDetailsMeta($request)
    {
        return [
            'authorizedToShow' => $this->authorizedToShow($request),
            'authorizedToStore' => $this->authorizedToStore($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ];
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @param $request
     * @return array
     */
    public function resolveDetailsRelationships($request)
    {
        if (is_null($request->get('with'))) {
            return [];
        }

        $withs = [];

        if ($this->resource instanceof RestifySearchable) {
            with(explode(',', $request->get('with')), function ($relations) use ($request, &$withs) {
                foreach ($relations as $relation) {
                    if (in_array($relation, $this->resource::getWiths())) {
                        /**
                         * @var AbstractPaginator
                         */
                        $paginator = $this->resource->{$relation}()->paginate($request->get('relatablePerPage') ?? ($this->resource::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE));
                        /** * @var Builder $q */
                        $q = $this->resource->{$relation}->first();
                        /** * @var Repository $repository */
                        if ($q && $repository = Restify::repositoryForModel($q->getModel())) {
                            // This will serialize into the repository dedicated for model
                            $relatable = $paginator->getCollection()->map(function ($value) use ($repository) {
                                return $repository::resolveWith($value);
                            });
                        } else {
                            // This will fallback into serialization of the parent formatting
                            $relatable = $paginator->getCollection()->map(function ($value) use ($repository) {
                                return $repository::resolveWith($value);
                            });
                        }

                        unset($relatable['meta']);
                        unset($relatable['links']);

                        $withs[$relation] = $relatable;
                    }
                }
            });
        }

        return $withs;
    }

    /**
     * Resolve the response for the details.
     *
     * @param $request
     * @param $serialized
     * @return array
     */
    public function serializeDetails(RestifyRequest $request, $serialized)
    {
        return $serialized;
    }

    /**
     * Resolve the response for the index request.
     *
     * @param $request
     * @param $serialized
     * @return array
     */
    public function serializeIndex($request, $serialized)
    {
        return $serialized;
    }

    /**
     * Return the attributes list.
     *
     * @param $request
     * @return array
     */
    public function resolveIndexAttributes($request)
    {
        return $this->resolveDetailsAttributes($request);
    }

    /**
     * @param $request
     * @return array
     */
    public function resolveIndexMeta($request)
    {
        return $this->resolveDetailsMeta($request);
    }

    /**
     * Return a list with relationship for the current model.
     *
     * @param $request
     * @return array
     */
    public function resolveIndexRelationships($request)
    {
        return $this->resolveDetailsRelationships($request);
    }
}
