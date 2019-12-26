<?php

namespace Binaryk\LaravelRestify\Repositories;

/**
 * @package Binaryk\LaravelRestify\Repositories;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait ResponseResolver
{

    /**
     * Return the attributes list
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
     * Return a list with relationship for the current model
     *
     * @param $request
     * @return array
     */
    public function resolveDetailsRelationships($request)
    {
        return [];
    }

    /**
     * Triggered after toArray
     *
     * @param $serialized
     * @return array
     */
    public function resolveDetails($serialized)
    {
        return $serialized;
    }

    /**
     * Return the attributes list
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
     * Return a list with relationship for the current model
     *
     * @param $request
     * @return array
     */
    public function resolveIndexRelationships($request)
    {
        return $this->resolveDetailsRelationships($request);
    }
}
