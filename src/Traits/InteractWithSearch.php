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
}
