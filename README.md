<p align="center"><img src="/docs-v2/static/logo.png"></p>

<p align="center">
    <a href="https://github.com/BinarCode/laravel-restify/actions"><img src="https://github.com/BinarCode/laravel-restify/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/license.svg" alt="License"></a>
</p>

# Unified Laravel API Layer for Humans and AI

**One Codebase. REST for Humans, MCP for AI Agents.**

Laravel Restify turns your Eloquent models into both JSON:API endpoints and MCP servers -- automatically. Build once, and instantly serve APIs that work seamlessly for developers, apps, and AI agents.

## 🚀 The Power of One Codebase

Traditional API development requires separate implementations for different consumers. Laravel Restify changes that:

- **👥 Humans** get full-featured JSON:API endpoints
- **🤖 AI Agents** get structured MCP (Model Context Protocol) servers
- **🔒 Same Rules** - All authentication, authorization, and policies apply to both
- **📝 One Definition** - Write your repository once, serve everywhere

## Key Features

- **JSON:API Endpoints** - Full [JSON:API](https://jsonapi.org) specification compliance
- **MCP Server Generation** - Automatic AI agent interfaces with tool definitions
- **Unified Authorization** - Laravel policies protect both human and AI access
- **Search & Filtering** - Powerful query capabilities for all consumers
- **Authentication** - Laravel Sanctum integration for secure access
- **GraphQL Support** - Auto-generated GraphQL schemas
- **Field Validation** - Consistent validation rules across all interfaces

## Installation

```bash
composer require binaryk/laravel-restify
```

## Quick Start

**1. Setup the package:**
```bash
php artisan restify:setup
```

**2. Create your first repository:**
```bash
php artisan restify:repository PostRepository --all
```

**3. Enable MCP for AI agents (optional):**

Add to your `config/ai.php`:
```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])
    ->name('mcp.restify');
```

**That's it!** Your API now serves both:

**For Humans (JSON:API):**
```bash
GET /api/restify/posts
POST /api/restify/posts
PUT /api/restify/posts/1
DELETE /api/restify/posts/1
```

**For AI Agents (MCP):**
```bash
GET /mcp/restify  # Tool definitions and capabilities
```

## Example Repository

```php
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

This single definition automatically provides:
- ✅ JSON:API CRUD endpoints with validation
- ✅ MCP tools for AI agents with the same validation
- ✅ Authorization policies applied to both interfaces
- ✅ Search and filtering capabilities for all consumers

## One Codebase, Two Outputs

Here's what you get from one repository definition:

### For Humans (JSON:API Response)
```bash
GET /api/restify/posts
```

```json
{
  "data": [
    {
      "id": "1",
      "type": "posts",
      "attributes": {
        "title": "Laravel Restify Guide",
        "content": "Build APIs fast...",
        "published_at": "2024-01-15"
      }
    }
  ],
  "links": {
    "self": "/api/restify/posts",
    "next": "/api/restify/posts?page=2"
  }
}
```

### For AI Agents (MCP Tool Definition)
```bash
GET /mcp/restify  # Tool definitions for AI agents
```

```json
{
  "tools": [
    {
      "name": "posts-index-tool",
      "description": "Retrieve a paginated list of Post records from the posts repository with filtering, sorting, and search capabilities.",
      "inputSchema": {
        "type": "object",
        "properties": {
          "page": {
            "type": "number",
            "description": "Page number for pagination"
          },
          "perPage": {
            "type": "number", 
            "description": "Number of posts per page"
          },
          "search": {
            "type": "string",
            "description": "Search term to filter posts by title or content"
          },
          "sort": {
            "type": "string",
            "description": "Sorting criteria (e.g., sort=title or sort=-published_at for descending)"
          },
          "title": {
            "type": "string",
            "description": "Filter by exact match for title (e.g., title=Laravel). Accepts negation with -title=value"
          },
          "published_at": {
            "type": "string",
            "description": "Filter by publication date (e.g., published_at=2024-01-15)"
          }
        }
      }
    }
  ]
}
```

**All generated from this simple repository:**
```php
#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->matchable(),
            field('content'),
            field('published_at')->rules('date')->matchable(),
        ];
    }
}
```

## Restify Boost

**Laravel Restify Boost** is a development companion that provides MCP server capabilities to help you build Restify APIs faster with AI assistance.

**Repository:** [laravel-restify-boost](https://github.com/BinarCode/laravel-restify-boost)

**Features:**
- Documentation access for AI agents
- Repository and action generation assistance
- Code examples and best practices
- Integration with Claude Desktop and other AI tools

**Installation:**
```bash
composer require --dev binarcode/laravel-restify-boost
```

## Templates

Need a production-ready starting point? Check out [Restify Templates](https://restifytemplates.com) for complete API starter kits with authentication, permissions, and team management.

## Resources

- **[Documentation](https://laravel-restify.com)** - Complete guides and API reference
- **[Demo Repository](https://github.com/BinarCode/restify-demo)** - Working example
- **[Video Course](https://www.binarcode.com/learn/restify)** - Visual learning resource

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email eduard.lupacescu@binarcode.com or [message me on twitter](https://twitter.com/LupacescuEuard) instead of using the issue tracker.

## Credits

- [Eduard Lupacescu](https://twitter.com/LupacescuEuard)
- [Koen Koenster](https://github.com/Koenster)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
