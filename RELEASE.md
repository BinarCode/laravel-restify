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

#### Enhanced Field Methods

New and improved field methods with flexible signatures:
- **`searchable()`** - Unified flexible signature with multiple argument support
- **`matchable()`** - Various match types and advanced filtering scenarios
- **`sortable()`** - Custom columns and conditional sorting

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