<?php

namespace Binaryk\LaravelRestify\Traits;

use Illuminate\Http\Request;

/**
 * @package Binaryk\LaravelRestify\Traits;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithSearch
{
    use AuthorizableModels;

    static $defaultPerPage = 15;

    /**
     * @return array
     */
    public function getSearchableFields()
    {
        return static::$search ?? [];
    }

    /**
     * @return array
     */
    public function getWiths()
    {
        return static::$withs ?? [];
    }

    /**
     * @return array
     */
    public function getInFields()
    {
        return static::$in ?? [];
    }

    /**
     * @return array
     */
    public function getMatchByFields()
    {
        return static::$match ?? [];
    }
    /**
     * @return array
     */
    public function getOrderByFields()
    {
        return static::$order ?? [];
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  Request  $request
     * @param  array  $fields
     * @return array
     */
    public function serializeForIndex(Request $request, array $fields = null)
    {
        return array_merge($fields ?: $this->toArray(), [
            'authorizedToView' => $this->authorizedToView($request),
            'authorizedToCreate' => $this->authorizedToCreate($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ]);
    }
}
