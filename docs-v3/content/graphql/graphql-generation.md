---
title: GraphQL Schema Generation
menuTitle: Schema Generation
category: GraphQL
position: 17
---

# GraphQL Schema Generation

Laravel Restify can automatically generate GraphQL schemas and resolvers from your existing Restify repositories, allowing you to quickly add GraphQL capabilities to your API.

## Overview

The GraphQL generation feature analyzes your Restify repositories and creates:

- **GraphQL Type Definitions** - Based on your repository fields
- **Query Operations** - For fetching individual resources and collections
- **Mutation Operations** - For creating, updating, and deleting resources
- **Input Types** - For mutation operations
- **Resolver Classes** - PHP classes that handle GraphQL operations (optional)

## Installation

### 1. Install Lighthouse GraphQL

First, install the Lighthouse GraphQL package:

```bash
composer require pusher/pusher-php-server lighthouse/lighthouse
```

### 2. Publish Lighthouse Configuration

```bash
php artisan vendor:publish --tag=lighthouse-config
```

## Basic Usage

### Generate GraphQL Schema

The simplest way to generate a GraphQL schema from your repositories:

```bash
php artisan restify:graphql:generate
```

This command will:
1. Analyze all registered Restify repositories
2. Show a preview of what will be generated
3. Ask for confirmation before proceeding
4. Generate a GraphQL schema file at `app/GraphQL/schema.graphql`

### Generate with Resolvers

To also generate PHP resolver classes:

```bash
php artisan restify:graphql:generate --resolvers
```

This creates resolver classes in `app/GraphQL/Resolvers/` that handle the GraphQL operations.

## Command Options

### Basic Options

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing files without prompting |
| `--skip-preview` | Skip the preview and generate files immediately |
| `--resolvers` | Generate PHP resolver classes |

### Output Configuration

| Option | Description | Default |
|--------|-------------|---------|
| `--output-path` | Directory for generated files | `app/GraphQL` |
| `--schema-file` | Name of the schema file | `schema.graphql` |

### Examples

```bash
# Generate to custom directory
php artisan restify:graphql:generate --output-path=resources/graphql

# Use custom schema filename
php artisan restify:graphql:generate --schema-file=api-schema.graphql

# Generate everything without prompts
php artisan restify:graphql:generate --resolvers --force --skip-preview
```

## Generated Schema Structure

### Type Definitions

For a `UserRepository`, the command generates:

```graphql
type User {
    id: ID!
    name: String
    email: String
    created_at: String
    updated_at: String
}

input UserInput {
    name: String
    email: String
}
```

### Query Operations

```graphql
type Query {
    user(id: ID!): User
    userList(first: Int = 15, page: Int = 1): [User!]!
}
```

### Mutation Operations

```graphql
type Mutation {
    createUser(input: UserInput!): User!
    updateUser(id: ID!, input: UserInput!): User!
    deleteUser(id: ID!): Boolean!
}
```

## Field Type Mapping

Laravel Restify fields are automatically mapped to GraphQL types:

| Restify Field | GraphQL Type |
|---------------|--------------|
| `Field`, `Text`, `Textarea` | `String` |
| `Number`, `Integer` | `Int` |
| `Float`, `Decimal` | `Float` |
| `Boolean`, `Toggle` | `Boolean` |
| `Date`, `DateTime` | `String` |
| `Json` | `JSON` |
| `MultiSelect` | `[String!]` |
| `BelongsTo` | `ID` (input) / `String` (output) |
| `HasMany` | `[ID!]` (input) / `[String!]` (output) |

## Generated Resolvers

When using `--resolvers`, the command creates resolver classes with standard CRUD operations:

```php
<?php

namespace App\GraphQL\Resolvers;

use App\Restify\UserRepository;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class UserResolver
{
    public function show($root, array $args, $context, $info)
    {
        $repository = new UserRepository();
        $model = $repository::newModel()->findOrFail($args['id']);

        return $repository->setModel($model)
            ->serializeForShow(RestifyRequest::createFrom(request()));
    }

    public function index($root, array $args, $context, $info)
    {
        $repository = new UserRepository();
        $query = $repository::newModel()->query();

        $page = $args['page'] ?? 1;
        $perPage = $args['first'] ?? 15;

        return $query->paginate($perPage, ['*'], 'page', $page)->items();
    }

    public function create($root, array $args, $context, $info)
    {
        $repository = new UserRepository();
        $request = RestifyRequest::createFrom(request());
        $request->merge($args['input']);

        return $repository->store($request);
    }

    public function update($root, array $args, $context, $info)
    {
        $repository = new UserRepository();
        $request = RestifyRequest::createFrom(request());
        $request->merge($args['input']);

        return $repository->update($request, $args['id']);
    }

    public function delete($root, array $args, $context, $info)
    {
        $repository = new UserRepository();
        $request = RestifyRequest::createFrom(request());

        $repository->destroy($request, $args['id']);

        return true;
    }
}
```

## Configuration

The generation process can be customized using the configuration file:

```bash
php artisan vendor:publish --tag=restify-graphql-config
```

This publishes `config/restify-graphql.php` where you can configure:

- Field type mappings
- Schema generation options
- Repository filtering
- Output formatting preferences

## Preview Mode

By default, the command shows a detailed preview before generating files:

```
📋 Preview of files to be generated:
═══════════════════════════════════════════════════════

🔍 Found 3 repositories:
   • UserRepository
   • PostRepository
   • CompanyRepository

📂 Output configuration:
   Output directory: app/GraphQL
   Schema file: schema.graphql
   Generate resolvers: Yes
   Force overwrite: No

📄 Files that will be generated:
   1. app/GraphQL/schema.graphql
   2. Resolvers directory: app/GraphQL/Resolvers/
      3. app/GraphQL/Resolvers/UserResolver.php
      4. app/GraphQL/Resolvers/PostResolver.php
      5. app/GraphQL/Resolvers/CompanyResolver.php

📝 Sample GraphQL schema preview:
   ┌─────────────────────────────────────────────────────┐
   │ type User {                                         │
   │   id: ID!                                           │
   │   name: String                                      │
   │   email: String                                     │
   │   created_at: String                                │
   │   updated_at: String                                │
   │ }                                                   │
   │                                                     │
   │ type Query {                                        │
   │   user(id: ID!): User                              │
   │   userList(first: Int, page: Int): [User!]!        │
   │ }                                                   │
   │                                                     │
   │ type Mutation {                                     │
   │   createUser(input: UserInput!): User!             │
   │   updateUser(id: ID!, input: UserInput!): User!    │
   │   deleteUser(id: ID!): Boolean!                     │
   │ }                                                   │
   │                                                     │
   │ # ... plus 2 more types                            │
   └─────────────────────────────────────────────────────┘
```

## Lighthouse Integration

After generating your schema and resolvers, configure Lighthouse to use them:

### 1. Update Lighthouse Config

Edit `config/lighthouse.php`:

```php
'schema' => [
    'register' => app_path('GraphQL/schema.graphql'),
],
```

### 2. Register Resolvers

If you generated resolver classes, register them in your GraphQL schema by adding directives:

```graphql
type Query {
    user(id: ID!): User @field(resolver: "App\\GraphQL\\Resolvers\\UserResolver@show")
    userList(first: Int = 15, page: Int = 1): [User!]! @field(resolver: "App\\GraphQL\\Resolvers\\UserResolver@index")
}

type Mutation {
    createUser(input: UserInput!): User! @field(resolver: "App\\GraphQL\\Resolvers\\UserResolver@create")
    updateUser(id: ID!, input: UserInput!): User! @field(resolver: "App\\GraphQL\\Resolvers\\UserResolver@update")
    deleteUser(id: ID!): Boolean! @field(resolver: "App\\GraphQL\\Resolvers\\UserResolver@delete")
}
```

### 3. Add GraphQL Route

Add the GraphQL endpoint to your routes:

```php
// routes/web.php or routes/api.php
Route::middleware(['api'])->group(function () {
    Route::post('/graphql', \Nuwave\Lighthouse\Http\GraphQLController::class);
});
```

## Authentication Context

The GraphQL generation command automatically handles authentication mocking in console context. This ensures that repositories with permission checks (like `$request->user()->can()`) work properly during schema generation.

The mock user created during generation:
- Always returns `true` for permission checks
- Provides basic user properties (`id: 1`)
- Handles any authentication-related method calls

## Next Steps

After generating your GraphQL schema:

1. **Install Lighthouse** if not already installed
2. **Configure Lighthouse** to use the generated schema
3. **Register resolvers** in your GraphQL setup
4. **Test your GraphQL endpoint** using tools like GraphQL Playground
5. **Customize the schema** as needed for your specific requirements

## Troubleshooting

### Field Collection Issues

If some fields are missing from the generated schema, ensure your repository's `fields()` method is properly implemented and doesn't have complex conditional logic that prevents field collection.

### Permission Errors

The command includes authentication mocking, but if you encounter permission-related errors, check that your repositories handle the mock authentication context properly.

### Custom Field Types

If you have custom field types that aren't mapped correctly, you can extend the type mapping in the configuration file or modify the generated schema manually.
