---
title: Basic Repositories
menuTitle: Basic Repositories
category: API
position: 4
---

The Repository is the core of Laravel Restify. It defines how your models are exposed through API endpoints, handling CRUD operations automatically while giving you full control over fields, validation, and authorization.

## Quick Start

Create a repository with the Artisan command:

```bash
php artisan restify:repository PostRepository
```

This creates `app/Restify/PostRepository.php` associated with your `Post` model.

## Basic Repository

Here's a minimal repository to get you started:

```php
namespace App\Restify;

use App\Models\Post;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Attributes\Model;

#[Model(Post::class)]
class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required(),
            field('content'),
            field('published_at')->nullable(),
        ];
    }
}
```

That's it! You now have a complete API with these endpoints:

| Method | URL | Action |
|--------|-----|---------|
| GET | `/api/restify/posts` | List all posts |
| GET | `/api/restify/posts/1` | Get a specific post |
| POST | `/api/restify/posts` | Create a new post |
| PUT | `/api/restify/posts/1` | Update a post |
| DELETE | `/api/restify/posts/1` | Delete a post |

## Model Association

Restify needs to know which Eloquent model your repository represents. There are three ways to define this:

### 1. Modern Approach (Recommended)

Use PHP 8+ attributes:

```php
#[Model(Post::class)]
class PostRepository extends Repository
{
    // Fields...
}
```

### 2. Static Property

```php
class PostRepository extends Repository
{
    public static string $model = Post::class;
    
    // Fields...
}
```

### 3. Auto-Guessing

If you don't specify a model, Restify will guess it from the repository name:
- `PostRepository` → `App\Models\Post`
- `BlogPostRepository` → `App\Models\BlogPost`

## Fields

The `fields()` method defines which model attributes are exposed through your API:

```php
public function fields(RestifyRequest $request): array
{
    return [
        field('title')
            ->required()
            ->rules('max:255'),
            
        field('content')
            ->rules('required'),
            
        field('status')
            ->default('draft'),
            
        field('published_at')
            ->nullable(),
    ];
}
```

### Field Types

Common field patterns for different data:

```php
field('title')->string(),                    // Text with string validation
field('content')->string(),                  // Content field  
textarea('content'),                         // Long text (helper function)
field('price')->numeric(),                   // Numbers
field('published')->boolean(),               // True/false
field('published_at')->date(),               // Dates
field('status')->string(),                   // Status field
```

### Field Validation

Add Laravel validation rules to fields:

```php
field('email')
    ->required()
    ->rules('email', 'unique:users,email'),

field('age')
    ->rules('integer', 'min:18'),
```

## CRUD Operations

### Creating Posts

**Request:**
```bash
POST /api/restify/posts
Content-Type: application/json

{
  "title": "My First Post",
  "content": "Hello World!"
}
```

**Response:**
```json
{
  "data": {
    "id": "1",
    "type": "posts",
    "attributes": {
      "title": "My First Post",
      "content": "Hello World!",
      "published_at": null
    }
  }
}
```

### Reading Posts

**List all posts:**
```bash
GET /api/restify/posts
```

**Get specific post:**
```bash
GET /api/restify/posts/1
```

### Updating Posts

```bash
PUT /api/restify/posts/1
Content-Type: application/json

{
  "title": "Updated Title",
  "published_at": "2024-01-15T10:00:00Z"
}
```

### Deleting Posts

```bash
DELETE /api/restify/posts/1
```

Returns `204 No Content` on success.

## Relationships

Define relationships in your repository to work with related models:

```php
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\HasMany;

class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title'),
            field('content'),
        ];
    }
    
    public static function related(): array
    {
        return [
            'author' => BelongsTo::make('user', UserRepository::class),
            'comments' => HasMany::make('comments', CommentRepository::class),
        ];
    }
}
```

### Loading Relationships

Include related data in your API responses:

```bash
GET /api/restify/posts?related=author,comments
```

## Search and Filtering

Make fields searchable, sortable, or filterable:

```php
public function fields(RestifyRequest $request): array
{
    return [
        field('title')
            ->searchable()   // Can search by title
            ->sortable(),    // Can sort by title
            
        field('status')
            ->matchable(),   // Can filter by exact status
            
        field('content')
            ->searchable(),  // Can search in content
    ];
}
```

### Using Search and Filters

```bash
# Search for posts containing "laravel"
GET /api/restify/posts?search=laravel

# Filter by status
GET /api/restify/posts?status=published

# Sort by title
GET /api/restify/posts?sort=title

# Sort descending
GET /api/restify/posts?sort=-title

# Combine multiple parameters
GET /api/restify/posts?search=laravel&status=published&sort=-created_at
```

## Pagination

All index requests are paginated automatically:

```bash
# Get page 2 with 10 items per page
GET /api/restify/posts?page=2&perPage=10
```

## Authorization

Protect your repositories with Laravel policies:

```php [app/Policies/PostPolicy.php]
class PostPolicy
{
    public function allowRestify(User $user = null): bool
    {
        return true;
    }
    
    public function show(User $user = null, Post $post): bool
    {
        return true;
    }
    
    public function store(User $user): bool
    {
        return $user->hasRole('editor');
    }
    
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }
    
    public function delete(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
```

Register your policy in `AuthServiceProvider`:

```php
protected $policies = [
    Post::class => PostPolicy::class,
];
```

## AI Integration (MCP)

Laravel Restify includes built-in support for AI agents through Model Context Protocol (MCP). To enable AI access to your repository, add the `HasMcpTools` trait:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required(),
            field('content'),
            field('published_at')->nullable(),
        ];
    }
}
```

This automatically creates MCP tools that AI agents can use to:
- List your posts
- Read specific posts  
- Create new posts (if you enable it)
- Update posts (if you enable it)

For detailed MCP configuration, see the [MCP Repositories](/docs/mcp/repositories) documentation.

## What's Next?

You now know the basics of Laravel Restify repositories! For more advanced features, check out:

- **[Advanced Repositories](/docs/api/repositories-advanced)** - Query customization, lifecycle events, custom routes
- **[Fields](/docs/api/fields)** - Advanced field types and customization  
- **[Authorization](/docs/auth/authorization)** - Advanced security and permissions
- **[MCP Integration](/docs/mcp/repositories)** - Full AI agent integration
