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

        /*
        |--------------------------------------------------------------------------
        | User Email Verification URL
        |--------------------------------------------------------------------------
        |
        | This URL is used to redirect users after they click the email verification
        | link. The API will validate the verification and redirect to this frontend
        | URL with success/failure query parameters.
        |
        | Available placeholders:
        | {id} - User ID
        | {emailHash} - SHA1 hash of user's email
        |
        | Query parameters added by API:
        | ?success=true&message=Email verified successfully.
        | ?success=false&message=Invalid or expired verification link.
        |
        */
        'user_verify_url' => env('FRONTEND_APP_URL').'/verify/{id}/{emailHash}',

        'user_model' => "\App\Models\User",

        /*
        |--------------------------------------------------------------------------
        | Token TTL (Time To Live)
        |--------------------------------------------------------------------------
        |
        | This value determines the number of minutes that authentication tokens
        | will be considered valid. After this time expires, users will need to
        | re-authenticate. Set to null for tokens that never expire.
        |
        | Default: null (never expires)
        |
        */
        'token_ttl' => env('RESTIFY_TOKEN_TTL', null),
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
        // 'auth:sanctum',
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

        /*
        |--------------------------------------------------------------------------
        | Use JOINs for BelongsTo Relationships
        |--------------------------------------------------------------------------
        |
        | When enabled, BelongsTo relationship searches will use JOINs instead of
        | subqueries for better performance. This is generally recommended for
        | better query performance, but can be disabled if compatibility issues arise.
        |
        | Default: true (recommended for better performance)
        |
        */
        'use_joins_for_belongs_to' => env('RESTIFY_USE_JOINS_FOR_BELONGS_TO', false),
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

        /*
        |--------------------------------------------------------------------------
        | Repository Index Caching
        |--------------------------------------------------------------------------
        |
        | These settings control caching for repository index requests. Caching
        | can significantly improve performance for expensive queries with filters,
        | searches, and sorts. Cache is automatically disabled in test environment.
        |
        */
        'cache' => [
            /*
            | Enable or disable repository index caching globally.
            | Individual repositories can override this setting.
            */
            'enabled' => env('RESTIFY_REPOSITORY_CACHE_ENABLED', false),

            /*
            | Default cache TTL in seconds for repository index requests.
            | Individual repositories can override this setting.
            */
            'ttl' => env('RESTIFY_REPOSITORY_CACHE_TTL', 300), // 5 minutes

            /*
            | Cache store to use. If null, uses the default cache store.
            */
            'store' => env('RESTIFY_REPOSITORY_CACHE_STORE'),

            /*
            | Skip caching for authenticated requests. Useful if you have
            | user-specific authorization that makes caching less effective.
            */
            'skip_authenticated' => env('RESTIFY_REPOSITORY_CACHE_SKIP_AUTHENTICATED', false),

            /*
            | Enable caching in test environment. By default, caching is
            | automatically disabled during testing to avoid test isolation issues.
            */
            'enable_in_tests' => env('RESTIFY_REPOSITORY_CACHE_ENABLE_IN_TESTS', false),

            /*
            | Default cache tags for all repositories. Individual repositories
            | can add their own tags in addition to these.
            |
            | Note: Cache tags are only used if the cache store supports them.
            | Database and file cache stores do not support tagging.
            | Redis and Memcached stores support tagging.
            */
            'tags' => ['restify', 'repositories'],
        ],
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
    'ai_solutions' => [
        /*
        | Specify the OpenAI model to use.
        */
        'model' => 'gpt-4.1-mini',

        /*
        | Specify the OpenAI temperature to use.
        */
        'max_tokens' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Context Protocol (MCP)
    |--------------------------------------------------------------------------
    |
    | These settings control the MCP integration that allows AI agents to
    | interact with your Restify repositories through structured tool interfaces.
    |
    */
    'mcp' => [
        'tools' => [
            'exclude' => [
                // Tool classes to exclude from discovery
                // 'App\MCP\Tools\SensitiveTool',
            ],
            'include' => [
                // Additional tool classes to include
                // 'App\MCP\Tools\CustomTool',
            ],
        ],
        'resources' => [
            'exclude' => [
                // Resource classes to exclude from discovery
            ],
            'include' => [
                // Additional resource classes to include
            ],
        ],
        'prompts' => [
            'exclude' => [
                // Prompt classes to exclude from discovery
            ],
            'include' => [
                // Additional prompt classes to include
            ],
        ],
    ],
];
