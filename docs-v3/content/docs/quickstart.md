---
title: Quickstart
category: Getting Started
---

## Requirements

Laravel Restify has a few minimum requirements you should be aware of before installing:

- Composer ^2.0
- PHP ^8.2
- Laravel Framework 11.x or 12.x

## Installation

```bash
composer require binaryk/laravel-restify
```

### Older Laravel Versions

For older versions of Laravel, check the appropriate branch and documentation in the [GitHub repository](https://github.com/BinarCode/laravel-restify).

## Setup

After installation, run the setup command to scaffold your API:

```shell script
php artisan restify:setup
```

This command will:

- **Publish** the `config/restify.php` configuration file and `action_logs` migration
- **Create** the `providers/RestifyServiceProvider` and register it automatically
- **Create** a new `app/Restify` directory for your repositories
- **Generate** an abstract `app/Restify/Repository.php` base class
- **Scaffold** a `app/Restify/UserRepository` for immediate use
- **Create** the `routes/ai.php` file (if it doesn't exist) with commented MCP server configuration

### Run Migrations

Complete the setup by running migrations:

```shell script
php artisan migrate
```

## First API Request

With setup complete, you can immediately test your API. Laravel Restify automatically creates endpoints for your User model:

```bash
# Standard pagination
GET /api/restify/users?perPage=10&page=1

# JSON:API format
GET /api/restify/users?page[size]=10&page[number]=1
```

### Example Response

```json
{
  "data": [
    {
      "type": "users",
      "id": "1",
      "attributes": {
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2023-01-01T00:00:00.000000Z"
      }
    }
  ],
  "links": {
    "first": "/api/restify/users?page=1",
    "last": "/api/restify/users?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

All responses follow the [JSON:API](https://jsonapi.org/format/) specification.

## Basic Configuration

### API Prefix

By default, all endpoints are prefixed with `/api/restify`. You can customize this in `config/restify.php`:

```php
'base' => '/api/v1', // Custom prefix
```

### Authentication Setup

For production use, enable authentication by uncommenting the Sanctum middleware:

```php [config/restify.php]
'middleware' => [
    'api',
    'auth:sanctum', // Uncomment this line
    Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent::class,
    Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify::class,
]
```

<alert>

**Need Authentication?** Check our [Authentication Guide](/auth/authentication) for detailed setup instructions including login endpoints and token management.

</alert>

## MCP Server Setup

Laravel Restify can automatically generate MCP (Model Context Protocol) servers for AI agents. 

**1. Add the MCP server to your `config/ai.php` file:**

```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;

// Web-based MCP server with authentication
Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])
    ->name('mcp.restify');
```

**2. Enable MCP tools in your repositories:**

Add the `HasMcpTools` trait to any repository you want to expose to AI agents:

```php
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools; // Enables MCP tools for AI agents
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->matchable(),
            field('content'),
        ];
    }
}
```

This creates an MCP endpoint at `/mcp/restify` that AI agents can use to discover and interact with your enabled repositories automatically.

<alert>

**Learn More:** Check the [MCP Server Guide](/mcp/mcp) for advanced configuration and usage with AI tools like Claude Desktop.

</alert>

## Creating Your First Repository

Let's create a `Post` repository to manage blog posts:

```shell script
# Generate repository only
php artisan restify:repository PostRepository

# Generate everything: repository, model, migration, and policy
php artisan restify:repository PostRepository --all
```

The `--all` flag creates:
- `app/Restify/PostRepository.php` - API repository
- `app/Models/Post.php` - Eloquent model
- `database/migrations/xxx_create_posts_table.php` - Database migration  
- `app/Policies/PostPolicy.php` - Authorization policy

### Example Repository

```php [app/Restify/PostRepository.php]
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Attributes\Model;

#[Model(Post::class)]
class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->rules('required', 'string', 'max:255'),
            textarea('content')->rules('required'),
            field('author')->readonly(),
            datetime('published_at')->nullable(),
        ];
    }
}
```

After creating the migration and running `php artisan migrate`, your Post API is ready at `/api/restify/posts`.

## Bulk Repository Generation

For existing Laravel projects, generate repositories for all models at once:

```shell script
php artisan restify:generate:repositories
```

This intelligent command:

1. **Discovers all models** in your application
2. **Analyzes database schema** to map field types  
3. **Shows a preview** of generated repositories
4. **Asks for confirmation** before creating files
5. **Generates repositories** with proper field definitions

### Useful Options

```shell script
# Generate for specific models only
php artisan restify:generate:repositories --only=User,Post

# Skip preview and generate immediately  
php artisan restify:generate:repositories --skip-preview

# Use domain-based structure
php artisan restify:generate:repositories --structure=domains
```

### Smart Field Mapping

The generator automatically maps database columns to Restify fields:

| Database Type | Restify Field                        |
|---------------|--------------------------------------|
| `any`         | `field()` (generic base field)       |
| `string`      | `text()` (wrapper for `field()` )    |
| `text`        | `textarea()` (wrapper for `field()`) |
| `integer`     | `number()` (wrapper for `field()`)   |
| `boolean`     | `boolean()` (wrapper for `field()`)  |
| `datetime`    | `datetime()` (wrapper for `field()`) |
| `json`        | `json()` (wrapper for `field()`)     |

**Special Cases:**
- Email columns → `email()` (wrapper for `field()`)
- Password fields → `password()` (wrapper for `field()`)

## Next Steps

Now that you have Restify running, explore these key features:

- **[Authentication](/auth/authentication)** - Secure your API with Sanctum
- **[Repositories](/api/repositories)** - Learn about fields, validation, and relationships  
- **[Search & Filtering](/search/basic-filters)** - Add powerful query capabilities
- **[Authorization](/auth/authorization)** - Control access with Laravel policies
- **[MCP Server](/mcp/mcp)** - Enable AI agents to interact with your API

### Generate Authorization Policies

Create policies for fine-grained access control:

```shell script
php artisan restify:policy PostPolicy
```

### Test Your API

Use these endpoints to test your setup:

```bash
# List all users
GET /api/restify/users

# Get specific user
GET /api/restify/users/1

# Create new user (if authentication disabled)
POST /api/restify/users

# Update user
PATCH /api/restify/users/1

# Delete user  
DELETE /api/restify/users/1
```
