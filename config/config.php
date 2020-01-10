<?php

use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent;

return [
    'auth' => [
        //Table with authenticatable resource
        'table' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Restify Base Route
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Restify route, giving you the
    | chance to add your own middleware to this stack or override any of
    | the existing middleware. Or, you can just stick with this stack.
    |
    */

    'base' => '/restify-api',

    /*
    |--------------------------------------------------------------------------
    | Restify Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Restify route, giving you the
    | chance to add your own middleware to this stack or override any of
    | the existing middleware. Or, you can just stick with this stack.
    |
    */

    'middleware' => [
        'api',
        DispatchRestifyStartingEvent::class,
        AuthorizeRestify::class,
    ],

    'exception_handler' => \Binaryk\LaravelRestify\Exceptions\RestifyHandler::class,
];
