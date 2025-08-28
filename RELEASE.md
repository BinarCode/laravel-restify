# Release Notes

This document provides a high-level overview of major features and changes in Laravel Restify. For detailed documentation and implementation guides, please refer to the comprehensive documentation.

## Version 10.x

### 🚀 Major Features

#### Model Context Protocol (MCP) Integration

Laravel Restify now provides seamless integration with the Model Context Protocol (MCP), allowing AI agents to interact with your REST API resources through structured tool interfaces. Transform your repositories into tools for AI agents to consume!

**Quick Setup:**
```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;

// Web-based MCP server with authentication
Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])
    ->name('mcp.restify');
```

**Key Benefits:** AI-Ready APIs, Zero Configuration, Built-in Security, Web & Terminal Access

📖 **[Complete MCP Documentation →](docs-v2/content/en/mcp/mcp.md)**

#### Lazy Relationship Loading for Fields

Fields can now be configured to lazy load relationships, preventing N+1 queries for computed attributes:

```php
field('profileTagNames', fn() => $this->model()->profileTagNames)
    ->lazy('tags'),
```

📖 **[Lazy Loading Documentation →](docs-v2/content/en/api/fields.md#lazy-loading)**

#### JOIN Optimization for BelongsTo Search

Performance optimization replacing slow subqueries with efficient JOIN operations. Enable via configuration:

```php
// config/restify.php
'search' => [
    'use_joins_for_belongs_to' => env('RESTIFY_USE_JOINS_FOR_BELONGS_TO', false),
],
```

📖 **[Performance Optimization Guide →](UPGRADING.md#join-optimization)**

#### Repository Index Caching

Powerful caching system for repository index requests that can improve response times by orders of magnitude. Features smart cache key generation, automatic invalidation, and support for all major cache stores.

```bash
# Enable in .env
RESTIFY_REPOSITORY_CACHE_ENABLED=true
RESTIFY_REPOSITORY_CACHE_TTL=300
RESTIFY_REPOSITORY_CACHE_STORE=redis
```

**Key Features:**
- **Zero Configuration**: Works out of the box with any cache store
- **Smart Invalidation**: Automatically clears cache on model changes  
- **User-Aware**: Respects authorization and user permissions
- **Test Safe**: Disabled by default in test environment
- **Store Agnostic**: Works with Redis, Database, File, and Memcached stores

**Performance Impact:**
- Complex queries: 50-90% faster response times
- Large datasets: Significant database load reduction
- Pagination: Near-instant subsequent page loads

```php
// Repository-specific configuration
class PostRepository extends Repository {
    public static int $cacheTtl = 600; // 10 minutes
    public static array $cacheTags = ['posts', 'content'];
}
```

📖 **[Repository Caching Documentation →](docs-v2/content/en/performance/performance.md#repository-index-caching)**

#### Enhanced Field Methods

New and improved field methods with flexible signatures:
- **`searchable()`** - Unified flexible signature with multiple argument support
- **`matchable()`** - Various match types and advanced filtering scenarios
- **`sortable()`** - Custom columns and conditional sorting

#### Custom Search Callbacks for BelongsTo Relations

BelongsTo fields now support custom search callbacks for complete control over search behavior:

```php
BelongsTo::make('user')->searchable(function ($query, $request, $value, $field, $repository) {
    return $query->whereHas('user', function ($q) use ($value) {
        $q->where('name', 'ilike', "%{$value}%")
          ->orWhere('email', 'ilike', "%{$value}%");
    });
})
```

The callback receives all necessary parameters with the query as the first parameter for maximum flexibility.

📖 **[Field Methods Documentation →](docs-v2/content/en/api/fields.md)**

### ⚠️ Breaking Changes

#### Default Search Behavior Change

Repositories no longer search by primary key (ID) by default when no searchable fields are defined.

**Migration Path:**
```php
public static function searchables(): array {
    return empty(static::$search) ? [static::newModel()->getKeyName()] : static::$search;
}
```

📖 **[Complete Migration Guide →](UPGRADING.md)**

### 🔧 Technical Improvements

- **Scout Integration**: Enhanced error handling and graceful degradation
- **Column Qualification**: Improved handling for JOIN operations
- **SearchablesCollection**: Fixed string callable handling
- **Configuration**: New options with environment variable support

## 📚 Documentation & Resources

- **[Complete Documentation](docs-v2/content/en/)** - Comprehensive guides and examples
- **[Migration Guide](UPGRADING.md)** - Step-by-step upgrade instructions
- **[MCP Integration](docs-v2/content/en/mcp/mcp.md)** - AI agent setup and configuration
- **[Field Reference](docs-v2/content/en/api/fields.md)** - All field methods and options

## 🧪 Testing

All new features include comprehensive test coverage to ensure reliability and maintainability.