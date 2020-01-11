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

    /*
    |--------------------------------------------------------------------------
    | Restify Exception Handler
    |--------------------------------------------------------------------------
    |
    | These will override the main application exception handler,
    | set to null, it will not override it.
    | Having RestifyHandler as a global exception handler is a good approach, since it
    | will return the exceptions in an API pretty format.
    */
    'exception_handler' => \Binaryk\LaravelRestify\Exceptions\RestifyHandler::class,
];
