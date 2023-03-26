<?php

use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent;
use Binaryk\LaravelRestify\Repositories\ActionLogRepository;

return [
    'auth' => [
        /*
        |--------------------------------------------------------------------------
        | Table containing authenticatable resource
        |--------------------------------------------------------------------------
        |
        | This configuration contain the name of the table used for the authentication.
        |
        */

        'table' => 'users',

        /*
        |--------------------------------------------------------------------------
        |
        |--------------------------------------------------------------------------
        |
        | Next you may configure the package you're using for the personal tokens generation,
        | this will be used for the verification of the authenticatable model and provide the
        | authorizable functionality
        |
        | Supported: "sanctum"
        */

        'provider' => 'sanctum',

        /*
        |--------------------------------------------------------------------------
        | Auth frontend app url
        |--------------------------------------------------------------------------
        |
        |URL used for reset password URL generating.
        |
        |
        */

        'frontend_app_url' => env('FRONTEND_APP_URL', env('APP_URL')),

        'password_reset_url' => env('FRONTEND_APP_URL').'/password/reset?token={token}&email={email}',

        'user_verify_url' => env('FRONTEND_APP_URL').'/verify/{id}/{emailHash}',

        'user_model' => "\App\Models\User",
    ],

    /*
    |--------------------------------------------------------------------------
    | RestifyJS
    |--------------------------------------------------------------------------
    |
    | This configuration is used for supporting the RestifyJS
    |
    */
    'restifyjs' => [
        /*
        | Token to authorize the setup endpoint.
        */
        'token' => env('RESTIFYJS_TOKEN', 'testing'),

        /*
        | The API base url.
        */
        'api_url' => env('API_URL', env('APP_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Restify Base Route
    |--------------------------------------------------------------------------
    |
    | This configuration is used as a prefix path where Restify will be accessible from.
    | Feel free to change this path to anything you like.
    |
    */

    'base' => '/api/restify',

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
        //'auth:sanctum',
        DispatchRestifyStartingEvent::class,
        AuthorizeRestify::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Restify Logs
    |--------------------------------------------------------------------------
    */
    'logs' => [
        /*
        | Repository used to list logs.
        */
        'repository' => ActionLogRepository::class,

        /**
         | Inform restify to log or not action logs.
         */
        'enable' => env('RESTIFY_ENABLE_LOGS', true),

        /**
        | Inform restify to log model changes from any source, or just restify. Set to `false` to log just restify logs.
         */
        'all' => env('RESTIFY_WRITE_ALL_LOGS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Restify Search
    |--------------------------------------------------------------------------
    */
    'search' => [
        /*
        | Specify either the search should be case-sensitive or not.
        */
        'case_sensitive' => true,
    ],

    'repositories' => [

        /*
        | Specify either to serialize index meta (policy) information or not. For performance reasons we recommend disabling it.
        */
        'serialize_index_meta' => false,

        /*
        | Specify either to serialize show meta (policy) information or not.
        */
        'serialize_show_meta' => true,
    ],

    'cache' => [
        /*
        | Specify the cache configuration for the resources policies.
        | When enabled, methods from the policy will be cached for the active user.
        */
        'policies' => [
            'enabled' => false,

            'ttl' => 5 * 60, // seconds
        ],
    ],

    /*
    | Specify if restify can call OpenAI for solution generation.
    |
    | By default this feature is enabled, but you still have to extend the Exception handler with the Restify one and set the API key.
     */
    'ai_solutions' => true,
];
