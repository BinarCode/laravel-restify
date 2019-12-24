<?php

namespace Binaryk\LaravelRestify\Traits;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Str;

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
        $serialized = [
            'type' => $request->isResolvedByRestify() ? static::uriKey() : Str::plural(Str::kebab(class_basename(get_called_class()))),
            'attributes' => $fields ?: $this->toArray(),
            'meta' => [
                'authorizedToView' => $this->authorizedToView($request),
                'authorizedToCreate' => $this->authorizedToCreate($request),
                'authorizedToUpdate' => $this->authorizedToUpdate($request),
                'authorizedToDelete' => $this->authorizedToDelete($request),
            ],
        ];

        if ($this->getKey()) {
            $serialized['id'] = $this->getKey();
        }

        return $serialized;
    }

    /**
     * @return array
     */
    abstract public function toArray();
}
