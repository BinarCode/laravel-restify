<?php

namespace Binaryk\LaravelRestify\Traits;

/**
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait InteractWithSearch
{
    use AuthorizableModels;

    public static $defaultPerPage = 15;

    public static $defaultRelatablePerPage = 15;

    public static function getSearchableFields()
    {
        return empty(static::$search)
            ? [static::newModel()->getKeyName()]
            : static::$search;
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
    public static function getRelated()
    {
        return static::$related ?? [];
    }

    /**
     * @return array
     */
    public static function getMatchByFields()
    {
        return empty(static::$match)
            ? [static::newModel()->getKeyName()]
            : static::$match;
    }

    /**
     * @return array
     */
    public static function getOrderByFields()
    {
        return empty(static::$sort)
            ? [static::newModel()->getKeyName()]
            : static::$sort;
    }
}
