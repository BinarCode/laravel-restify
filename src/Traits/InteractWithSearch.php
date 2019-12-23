<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithSearch
{
    use AuthorizableModels;

    public static $defaultPerPage = 15;

    /**
     * @return array
     */
    public static function getSearchableFields()
    {
        return static::$search ?? [];
    }

    /**
     * @return array
     */
    public static function getWiths()
    {
        return static::$withs ?? [];
    }

    /**
     * @return array
     */
    public static function getInFields()
    {
        return static::$in ?? [];
    }

    /**
     * @return array
     */
    public static function getMatchByFields()
    {
        return static::$match ?? [];
    }

    /**
     * @return array
     */
    public static function getOrderByFields()
    {
        return static::$sort ?? [];
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  RestifyRequest  $request
     * @param  array  $fields
     * @return array
     */
    public function serializeForIndex(RestifyRequest $request, array $fields = null)
    {
        return array_merge($fields ?: $this->toArray(), [
            'authorizedToView' => $this->authorizedToView($request),
            'authorizedToCreate' => $this->authorizedToCreate($request),
            'authorizedToUpdate' => $this->authorizedToUpdate($request),
            'authorizedToDelete' => $this->authorizedToDelete($request),
        ]);
    }
}
