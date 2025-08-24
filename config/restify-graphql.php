<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GraphQL Schema Generation
    |--------------------------------------------------------------------------
    |
    | Configure how Laravel Restify generates GraphQL schemas from your
    | repositories. These settings control the output and behavior of
    | the `php artisan restify:graphql:generate` command.
    |
    */

    'schema' => [
        /*
         * Default output path for generated GraphQL schema and resolvers
         */
        'output_path' => base_path('app/GraphQL'),

        /*
         * Default schema file name
         */
        'schema_file' => 'schema.graphql',

        /*
         * Whether to generate resolver classes by default
         */
        'generate_resolvers' => true,

        /*
         * Namespace for generated resolver classes
         */
        'resolver_namespace' => 'App\\GraphQL\\Resolvers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Type Mapping
    |--------------------------------------------------------------------------
    |
    | Configure how Restify field types map to GraphQL types. You can
    | customize these mappings to match your specific requirements.
    |
    */

    'type_mapping' => [
        // Basic types
        'Field' => 'String',
        'Text' => 'String',
        'Textarea' => 'String',
        'Email' => 'String',
        'Password' => 'String',
        'Url' => 'String',
        
        // Numeric types
        'Number' => 'Int',
        'Integer' => 'Int',
        'Float' => 'Float',
        'Decimal' => 'Float',
        
        // Boolean
        'Boolean' => 'Boolean',
        'Toggle' => 'Boolean',
        
        // Date/Time
        'Date' => 'String', // Consider using custom Date scalar
        'DateTime' => 'String', // Consider using custom DateTime scalar
        'Time' => 'String',
        
        // Relationships
        'BelongsTo' => 'ID', // For input types, reference for output types
        'HasMany' => '[ID!]', // For input types, array for output types
        'HasOne' => 'ID',
        'MorphTo' => 'ID',
        'MorphOne' => 'ID',
        'MorphMany' => '[ID!]',
        
        // File handling
        'File' => 'String', // File path/URL
        'Image' => 'String', // Image path/URL
        
        // Special types
        'Json' => 'JSON', // Requires custom JSON scalar
        'Select' => 'String',
        'MultiSelect' => '[String!]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Customization
    |--------------------------------------------------------------------------
    |
    | Configure additional schema generation options and customizations.
    |
    */

    'schema_options' => [
        /*
         * Include timestamps (created_at, updated_at) in generated types
         */
        'include_timestamps' => true,

        /*
         * Include soft delete timestamp (deleted_at) in generated types
         */
        'include_soft_deletes' => false,

        /*
         * Generate pagination types for list queries
         */
        'generate_pagination' => true,

        /*
         * Default pagination limit
         */
        'default_pagination_limit' => 15,

        /*
         * Maximum pagination limit
         */
        'max_pagination_limit' => 100,

        /*
         * Include authorization checks in generated resolvers
         */
        'include_authorization' => true,

        /*
         * Include validation in generated resolvers
         */
        'include_validation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which repositories should be included in GraphQL schema
    | generation and how they should be processed.
    |
    */

    'repositories' => [
        /*
         * Automatically discover repositories from the configured namespace
         */
        'auto_discover' => true,

        /*
         * Repository namespace to scan for auto-discovery
         */
        'namespace' => 'App\\Restify',

        /*
         * Specific repositories to include (if auto_discover is false)
         */
        'include' => [
            // 'App\\Restify\\UserRepository',
            // 'App\\Restify\\PostRepository',
        ],

        /*
         * Repositories to exclude from schema generation
         */
        'exclude' => [
            // 'App\\Restify\\InternalRepository',
        ],

        /*
         * Custom type names for repositories
         * Key: Repository class name, Value: GraphQL type name
         */
        'custom_type_names' => [
            // 'UserRepository' => 'User',
            // 'BlogPostRepository' => 'Article',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Options
    |--------------------------------------------------------------------------
    |
    | Advanced configuration options for GraphQL integration.
    |
    */

    'advanced' => [
        /*
         * Custom scalar types to register
         */
        'custom_scalars' => [
            'Date' => 'App\\GraphQL\\Scalars\\DateScalar',
            'DateTime' => 'App\\GraphQL\\Scalars\\DateTimeScalar',
            'JSON' => 'Nuwave\\Lighthouse\\Schema\\Types\\Scalars\\JSON',
        ],

        /*
         * Custom directives to include in schema
         */
        'custom_directives' => [
            '@can' => 'Nuwave\\Lighthouse\\Schema\\Directives\\CanDirective',
            '@guard' => 'Nuwave\\Lighthouse\\Schema\\Directives\\GuardDirective',
        ],

        /*
         * Generate subscription types for real-time updates
         */
        'generate_subscriptions' => false,

        /*
         * Subscription channels configuration
         */
        'subscriptions' => [
            'channels' => [
                'created' => '{type}_created',
                'updated' => '{type}_updated',
                'deleted' => '{type}_deleted',
            ],
        ],
    ],
];