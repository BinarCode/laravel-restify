<?php

namespace Binaryk\LaravelRestify\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @package Binaryk\LaravelRestify;
 * @author Eduard Lupacescu <eduard.lupacescu@binarcode.com>
 */
trait AuthorizableModels
{
    /**
     * Determine if the given resource is authorizable.
     *
     * @return bool
     */
    public static function authorizable()
    {
        return ! is_null(Gate::getPolicyFor(static::newModel()));
    }

    /**
     * Determine if the resource should be available for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function authorizedToViewAny(Request $request)
    {
        if ( ! static::authorizable()) {
            return true;
        }

        return method_exists(Gate::getPolicyFor(static::newModel()), 'viewAny')
            ? Gate::check('viewAny', get_class(static::newModel()))
            : true;
    }

}
