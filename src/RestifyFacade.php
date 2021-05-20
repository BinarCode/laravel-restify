<?php

namespace Binaryk\LaravelRestify;

use Illuminate\Support\Facades\Facade;

class RestifyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-restify';
    }
}
