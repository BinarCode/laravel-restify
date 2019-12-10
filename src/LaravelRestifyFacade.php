<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Binaryk\LaravelRestify\Skeleton\SkeletonClass
 */
class LaravelRestifyFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-restify';
    }
}
