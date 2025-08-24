---
title: GraphQL Integration
menuTitle: GraphQL
category: GraphQL
position: 16
---

# GraphQL Integration

Laravel Restify provides powerful GraphQL integration, allowing you to automatically generate GraphQL schemas and resolvers from your existing Restify repositories. This enables you to quickly add GraphQL capabilities to your API without rewriting your business logic.

## Overview

The GraphQL integration in Laravel Restify provides:

- **Automatic Schema Generation** - Convert your Restify repositories into GraphQL type definitions
- **Resolver Generation** - Create PHP resolver classes for handling GraphQL operations
- **Field Mapping** - Intelligent mapping from Restify fields to GraphQL types
- **CRUD Operations** - Full Create, Read, Update, Delete operations via GraphQL
- **Authentication Context** - Proper handling of authentication in console/generation context
- **Preview Mode** - See what will be generated before creating files

## Quick Start

### 1. Install Dependencies

First, install the required GraphQL packages:

```bash
composer require pusher/pusher-php-server lighthouse/lighthouse
```

### 2. Generate Your Schema

Use the Artisan command to generate GraphQL schema from your repositories:

```bash
php artisan restify:graphql:generate --resolvers
```

This command will:
- Analyze your Restify repositories
- Generate GraphQL type definitions
- Create resolver classes (if `--resolvers` flag is used)
- Show a preview before generating files

### 3. Configure Lighthouse

Update your `config/lighthouse.php` to use the generated schema:

```php
'schema' => [
    'register' => app_path('GraphQL/schema.graphql'),
],
```

### 4. Add GraphQL Endpoint

Add the GraphQL route to your application:

```php
// routes/api.php
Route::middleware(['api'])->group(function () {
    Route::post('/graphql', \Nuwave\Lighthouse\Http\GraphQLController::class);
});
```

## Example Generated Schema

For a `UserRepository` with fields like `name`, `email`, and `created_at`, the generator creates:

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

type Query {
    user(id: ID!): User
    userList(first: Int = 15, page: Int = 1): [User!]!
}

type Mutation {
    createUser(input: UserInput!): User!
    updateUser(id: ID!, input: UserInput!): User!
    deleteUser(id: ID!): Boolean!
}
```

## Benefits

### Rapid Development
- Generate complete GraphQL APIs from existing Restify repositories
- No need to manually write type definitions or resolvers
- Maintain consistency between REST and GraphQL APIs

### Automatic Type Safety
- Field types are automatically mapped to appropriate GraphQL types
- Input validation leverages existing Restify field definitions
- Type safety maintained throughout the stack

### Seamless Integration
- Uses existing Restify authorization and field visibility rules
- Leverages repository business logic and relationships
- Maintains consistency with REST API behavior

## Use Cases

### API Unification
Convert your REST API to also support GraphQL without duplicating logic:

```bash
# Generate GraphQL from existing repositories
php artisan restify:graphql:generate --resolvers --force
```

### Mobile App Backend
Provide GraphQL for mobile apps while maintaining REST for web clients:

```bash
# Generate to mobile-specific directory
php artisan restify:graphql:generate --output-path=app/GraphQL/Mobile --resolvers
```

### Third-Party Integrations
Create GraphQL schemas for external services that prefer GraphQL:

```bash
# Generate with custom schema naming
php artisan restify:graphql:generate --schema-file=external-api.graphql
```

## Next Steps

1. **[Schema Generation](/graphql/graphql-generation)** - Learn about the schema generation command and its options
2. **[Lighthouse Documentation](https://lighthouse-php.com/)** - Explore advanced GraphQL features
3. **[GraphQL Playground](https://github.com/graphql/graphql-playground)** - Test your GraphQL API interactively

## Configuration

The GraphQL generation process can be customized through the configuration file. Publish it using:

```bash
php artisan vendor:publish --tag=restify-graphql-config
```

This allows you to customize:
- Field type mappings
- Repository filtering
- Output formatting preferences
- Schema generation options
