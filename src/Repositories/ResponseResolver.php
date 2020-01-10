<?php

namespace Binaryk\LaravelRestify\Repositories;

use Binaryk\LaravelRestify\Contracts\RestifySearchable;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait ResponseResolver
{
    /**
     * Return the attributes list.
     *
     * @param $request
     * @return array
     */
    public function resolveDetailsAttributes($request)
    {
        return parent::toArray($request);
    }

    /**
     * @param $request
     * @return array
     */
    public function resolveDetailsMeta($request)
    {
        return [
            'authorizedToView' => $this->authorizedToView($request),
            'authorizedToCreate' => $this->authorizedToCreate($request),
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
                        $relatable = static::resolveWith($this->resource->{$relation}()->paginate($request->get('relatablePerPage') ?? ($this->resource::$defaultRelatablePerPage ?? RestifySearchable::DEFAULT_RELATABLE_PER_PAGE)))->toArray($request);
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
     * Resolve the response for the details
     *
     * @param $request
     * @param $serialized
     * @return array
     */
    public function serializeDetails($request, $serialized)
    {
        return $serialized;
    }

    /**
     * Resolve the response for the index request
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
