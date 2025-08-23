---
title: Model Context Protocol (MCP)
menuTitle: MCP
category: MCP
position: 1
---

Laravel Restify provides seamless integration with the Model Context Protocol (MCP), allowing AI agents to interact with your REST API resources through structured tool interfaces. This enables powerful AI-driven data access and manipulation while maintaining security and control.

## Overview

The MCP integration in Laravel Restify automatically exposes your repositories as MCP tools, providing AI agents with:

- **Structured data access** through well-defined tool schemas
- **CRUD operations** (Create, Read, Update, Delete) for each repository
- **Field-level visibility control** for sensitive data
- **Automatic tool discovery** and registration
- **Request validation** and error handling

## Setup & Registration

### Basic Server Registration

Register the MCP server in your application's service provider or routes file:

```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;

// Register the MCP server
Mcp::web('restify', RestifyServer::class)->name('mcp.restify');
```

This creates an MCP server endpoint at `/mcp/restify` that AI agents can connect to.

### Adding Authentication & Middleware

For production applications, you'll want to add authentication and other middleware:

```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Laravel\Mcp\Facades\Mcp;
use App\Http\Middleware\AccessTenantMiddleware;

Mcp::web('restify', RestifyServer::class)->middleware([
    'auth:sanctum',
    AccessTenantMiddleware::class,
])->name('mcp.restify');
```

This setup:
- **Requires authentication** using Laravel Sanctum
- **Applies tenant isolation** through custom middleware
- **Names the route** for easy reference

### Custom Server Implementation

You can extend the `RestifyServer` class to customize behavior:

```php
<?php

namespace App\MCP;

use Binaryk\LaravelRestify\MCP\RestifyServer;

class CustomRestifyServer extends RestifyServer
{
    public string $serverName = 'My App MCP Server';
    public string $serverVersion = '1.0.0';
    public string $instructions = 'Custom MCP server for MyApp with enhanced security and logging.';
    
    public function boot(): void
    {
        // Add custom logging
        logger()->info('MCP Server booting', ['user' => auth()->id()]);
        
        // Call parent boot method
        parent::boot();
        
        // Add custom tools
        $this->addCustomTools();
    }
    
    protected function addCustomTools(): void
    {
        // Register custom tools that aren't auto-discovered
        $this->addTool(new CustomAnalyticsTool());
        $this->addTool(new CustomReportTool());
    }
    
    protected function discoverRepositoryTools(): void
    {
        // Add custom filtering logic
        if (!auth()->user()?->hasRole('mcp-access')) {
            return; // Don't discover any tools for unauthorized users
        }
        
        parent::discoverRepositoryTools();
    }
}
```

Then register your custom server:

```php
use App\MCP\CustomRestifyServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('restify', CustomRestifyServer::class)->middleware([
    'auth:sanctum',
    'verified',
    'role:mcp-access',
])->name('mcp.restify');
```

### Advanced Authentication Patterns

#### API Token Authentication

```php
Mcp::web('restify', RestifyServer::class)->middleware([
    'auth:api',
    'throttle:mcp', // Custom rate limiting for MCP
])->name('mcp.restify');
```

#### Multi-Tenant Authentication

```php
Mcp::web('restify', RestifyServer::class)->middleware([
    'auth:sanctum',
    'tenant.resolve', // Resolve tenant from token
    'tenant.scope',   // Scope data to tenant
])->name('mcp.restify');
```

#### Role-Based Access Control

```php
Mcp::web('restify', RestifyServer::class)->middleware([
    'auth:sanctum',
    'role:mcp-user', // Require specific role
    'permission:access-mcp', // Require specific permission
])->name('mcp.restify');
```

### Server Customization Examples

#### Logging & Monitoring

```php
class LoggingRestifyServer extends RestifyServer
{
    public function boot(): void
    {
        $this->logServerBoot();
        parent::boot();
        $this->registerMetrics();
    }
    
    protected function logServerBoot(): void
    {
        Log::info('MCP Server initialized', [
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
    }
    
    protected function registerMetrics(): void
    {
        // Register custom metrics or monitoring
        Metrics::increment('mcp.server.boot', [
            'user_id' => auth()->id(),
        ]);
    }
}
```

#### Environment-Specific Configuration

```php
class ConfigurableRestifyServer extends RestifyServer
{
    public function __construct()
    {
        $this->serverName = config('mcp.server_name', 'Laravel Restify');
        $this->serverVersion = config('mcp.server_version', '1.0.0');
        $this->instructions = config('mcp.instructions', $this->instructions);
        $this->defaultPaginationLength = config('mcp.pagination_length', 50);
    }
    
    protected function discoverRepositoryTools(): void
    {
        // Only discover tools in production if explicitly enabled
        if (app()->environment('production') && !config('mcp.production_enabled')) {
            return;
        }
        
        parent::discoverRepositoryTools();
    }
}
```

#### Development vs Production Servers

```php
// Development server with extensive tooling
class DevelopmentRestifyServer extends RestifyServer
{
    public function boot(): void
    {
        parent::boot();
        
        // Add development-only tools
        $this->addTool(new DatabaseQueryTool());
        $this->addTool(new CacheInspectionTool());
        $this->addTool(new LogViewerTool());
    }
}

// Production server with limited, secure tooling
class ProductionRestifyServer extends RestifyServer
{
    public function boot(): void
    {
        parent::boot();
        
        // Only essential tools in production
        $this->validateProductionSecurity();
    }
    
    protected function validateProductionSecurity(): void
    {
        if (!auth()->check()) {
            throw new UnauthorizedException('Authentication required for MCP access');
        }
        
        if (!auth()->user()->hasVerifiedEmail()) {
            throw new UnauthorizedException('Email verification required for MCP access');
        }
    }
}

// Register environment-specific server
$serverClass = app()->environment('production') 
    ? ProductionRestifyServer::class 
    : DevelopmentRestifyServer::class;

Mcp::web('restify', $serverClass)->middleware([
    'auth:sanctum',
    'verified',
])->name('mcp.restify');
```

## RestifyServer

The `RestifyServer` class is the core component that manages MCP tool discovery and registration. It extends Laravel MCP's base `Server` class and provides automatic discovery of tools, resources, and prompts.

### Server Configuration

```php
class RestifyServer extends Server
{
    public string $serverName = 'Laravel Restify';
    public string $serverVersion = '0.0.1';
    public string $instructions = 'Laravel Restify MCP server providing access to RESTful API resources...';
    public int $defaultPaginationLength = 50;
}
```

### Auto-Discovery System

The server automatically discovers and registers MCP components during the `boot()` process:

#### Repository Tool Discovery

```php
protected function discoverRepositoryTools(): void
{
    collect(Restify::$repositories)
        ->filter(function (string $repository) {
            return in_array(McpTools::class, class_uses_recursive($repository));
        })
        ->each(function (string $repository) {
            $repositoryInstance = app($repository);
            
            // Register tools based on allowed operations
            if ($repositoryInstance->mcpAllowsIndex()) {
                $this->addTool(new IndexTool($repository));
            }
            
            if ($repositoryInstance->mcpAllowsShow()) {
                $this->addTool(new ShowTool($repository));
            }
            
            // ... other operations
        });
}
```

The discovery system:

1. **Scans all registered repositories** in `Restify::$repositories`
2. **Filters repositories** that use the `McpTools` trait
3. **Checks operation permissions** using `mcpAllows*()` methods
4. **Registers appropriate tools** for each allowed operation

#### Tool Discovery

```php
protected function discoverTools(): array
{
    $excludedTools = config('restify.mcp.tools.exclude', []);
    $toolDir = new \DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'Tools');
    
    foreach ($toolDir as $toolFile) {
        if ($toolFile->isFile() && $toolFile->getExtension() === 'php') {
            $fqdn = 'Binaryk\\LaravelRestify\\MCP\\Tools\\'.$toolFile->getBasename('.php');
            if (class_exists($fqdn) && ! in_array($fqdn, $excludedTools, true)) {
                $this->addTool($fqdn);
            }
        }
    }
    
    return $this->registeredTools;
}
```

### Configuration Options

You can control tool discovery through configuration:

```php
// config/restify.php
'mcp' => [
    'tools' => [
        'exclude' => [
            // Tool classes to exclude from discovery
            'App\MCP\Tools\SensitiveTool',
        ],
        'include' => [
            // Additional tool classes to include
            'App\MCP\Tools\CustomTool',
        ],
    ],
    'resources' => [
        'exclude' => [/* excluded resources */],
        'include' => [/* additional resources */],
    ],
    'prompts' => [
        'exclude' => [/* excluded prompts */],
        'include' => [/* additional prompts */],
    ],
],
```

## McpTools Trait

The `McpTools` trait provides repositories with MCP capabilities. Add this trait to your repository to enable MCP tool generation:

```php
use Binaryk\LaravelRestify\MCP\Concerns\McpTools;

class UserRepository extends Repository
{
    use McpTools;
    
    // Repository implementation...
}
```

### Operation Control Methods

Control which CRUD operations are exposed as MCP tools:

#### mcpAllowsIndex()

```php
public function mcpAllowsIndex(): bool
{
    return true; // Default: allow index operations
}
```

Override to control index tool availability:

```php
public function mcpAllowsIndex(): bool
{
    return auth()->user()?->can('viewAny', static::$model);
}
```

#### mcpAllowsShow()

```php
public function mcpAllowsShow(): bool
{
    return true; // Default: allow show operations
}
```

#### mcpAllowsStore()

```php
public function mcpAllowsStore(): bool
{
    return true; // Default: allow create operations
}
```

#### mcpAllowsUpdate()

```php
public function mcpAllowsUpdate(): bool
{
    return true; // Default: allow update operations
}
```

#### mcpAllowsDelete()

```php
public function mcpAllowsDelete(): bool
{
    return true; // Default: allow delete operations
}
```

### Tool Implementation Methods

These methods handle the actual tool operations and can be overridden for custom behavior:

#### indexTool()

```php
public function indexTool(array $arguments, McpRequest $request): array
{
    $request->merge($arguments);
    $this->sanitizeToolRequest($request, $arguments);
    
    return $this->indexAsArray($request);
}
```

**Arguments:**
- `$arguments` - Raw arguments from MCP tool call
- `$request` - McpRequest instance for processing

**Returns:** Paginated array of repository items

**Override example:**
```php
public function indexTool(array $arguments, McpRequest $request): array
{
    // Add custom logging
    Log::info('MCP Index accessed', ['repository' => static::class, 'args' => $arguments]);
    
    // Custom argument processing
    if (isset($arguments['admin_mode']) && !$request->user()->isAdmin()) {
        unset($arguments['admin_mode']);
    }
    
    $request->merge($arguments);
    
    return parent::indexTool($arguments, $request);
}
```

#### showTool()

```php
public function showTool(array $arguments, McpRequest $request): array
{
    $id = $arguments['id'] ?? null;
    unset($arguments['id']);
    $request->merge($arguments);
    
    $model = static::query($request)->findOrFail($id);
    return static::resolveWith($model)->showData($request);
}
```

**Arguments:**
- `$arguments` - Must contain `id` key for the record to show
- `$request` - McpRequest instance

**Returns:** Single repository item data

#### storeTool()

```php
public function storeTool(array $arguments, McpRequest $request): array
{
    $request->merge($arguments);
    
    return $this->store($request);
}
```

**Arguments:**
- `$arguments` - Data for creating new record
- `$request` - McpRequest instance

**Returns:** Created record data

#### updateTool()

```php
public function updateTool(array $arguments, McpRequest $request): array
{
    $id = $arguments['id'] ?? null;
    unset($arguments['id']);
    $request->merge($arguments);
    
    $model = static::query($request)->findOrFail($id);
    return static::resolveWith($model)->update($request, $id);
}
```

**Arguments:**
- `$arguments` - Must contain `id` key plus update data
- `$request` - McpRequest instance

**Returns:** Updated record data

#### deleteTool()

```php
public function deleteTool(array $arguments, McpRequest $request): array
{
    $id = $arguments['id'] ?? null;
    unset($arguments['id']);
    $request->merge($arguments);
    
    $model = static::query($request)->findOrFail($id);
    return static::resolveWith($model)->destroy($request, $id);
}
```

**Arguments:**
- `$arguments` - Must contain `id` key for record to delete
- `$request` - McpRequest instance

**Returns:** Deletion confirmation data

### Schema Definition Methods

These static methods define the input schemas for MCP tools:

#### indexToolSchema()

Defines the schema for index tool parameters including pagination, filtering, sorting, and search options:

```php
public static function indexToolSchema(ToolInputSchema $schema): void
{
    $key = static::uriKey();
    
    $schema->number('page')->description('Page number for pagination');
    $schema->number('perPage')->description("Number of $key per page");
    $schema->string('include')->description('Comma-separated relationships to include');
    $schema->string('search')->description('Search term for filtering');
    $schema->string('sort')->description('Sorting criteria');
    
    // Dynamic filter parameters based on repository matches
    collect(static::matches())->each(function ($type, $matchFilter) use ($schema) {
        // Adds appropriate input types based on match filter types
    });
}
```

#### showToolSchema()

```php
public static function showToolSchema(ToolInputSchema $schema): void
{
    $modelName = class_basename(static::$model);
    
    $schema->string('id')
        ->description("The ID of the $modelName to retrieve")
        ->required();
    
    $schema->string('include')
        ->description('Comma-separated relationships to include');
}
```

#### storeToolSchema()

Automatically generates schema based on repository fields:

```php
public static function storeToolSchema(ToolInputSchema $schema): void
{
    $repository = static::resolveWith(app(static::$model));
    
    $repository->collectFields($request = app(McpRequest::class))
        ->forStore($request, $repository)
        ->withoutActions($request, $repository)
        ->each(function (Field $field) use ($schema, $repository) {
            $field->resolveToolSchema($schema, $repository);
        });
}
```

This method:
1. **Collects repository fields** appropriate for storing
2. **Excludes action fields** that shouldn't be in the schema
3. **Generates field schemas** using each field's `resolveToolSchema()` method

## Tool Naming Convention

MCP tools are automatically named using the pattern: `{repository-uri-key}-{operation}-tool`

Examples:
- `users-index-tool` - Lists users
- `users-show-tool` - Shows a specific user
- `posts-store-tool` - Creates a new post
- `orders-update-tool` - Updates an order
- `products-delete-tool` - Deletes a product

## Security Considerations

### Operation-Level Control

```php
public function mcpAllowsStore(): bool
{
    // Only allow creating records for authenticated admin users
    return auth()->check() && auth()->user()->isAdmin();
}
```

### Field-Level Control

Use field visibility methods to control what data AI agents can access:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('name'),
        field('email'),
        
        // Hide sensitive fields from MCP
        field('password')->hideFromMcp(),
        field('api_token')->hideFromMcp(),
        
        // Conditional visibility
        field('admin_notes')->hideFromMcp(function($request) {
            return !$request->user()?->isAdmin();
        }),
    ];
}
```

### Custom Request Processing

Override tool methods for custom security logic:

```php
public function indexTool(array $arguments, McpRequest $request): array
{
    // Rate limiting
    RateLimiter::hit('mcp-access:'.$request->user()->id);
    
    // Audit logging
    AuditLog::create([
        'user_id' => $request->user()->id,
        'action' => 'mcp_index',
        'resource' => static::class,
        'arguments' => $arguments,
    ]);
    
    return parent::indexTool($arguments, $request);
}
```

## Best Practices

### 1. Selective Tool Exposure

Don't expose all operations for every repository:

```php
class SensitiveDataRepository extends Repository
{
    use McpTools;
    
    public function mcpAllowsIndex(): bool { return false; }
    public function mcpAllowsShow(): bool { return auth()->user()?->isAdmin(); }
    public function mcpAllowsStore(): bool { return false; }
    public function mcpAllowsUpdate(): bool { return false; }
    public function mcpAllowsDelete(): bool { return false; }
}
```

### 2. Field Visibility Management

```php
public function fields(RestifyRequest $request)
{
    return [
        field('title'),
        field('description'),
        
        // Hide internal fields from MCP
        field('internal_notes')->hideFromMcp(),
        field('processing_status')->hideFromMcp(),
        
        // Show metadata only to MCP (hide from regular API)
        field('mcp_metadata')->showOnIndex(false)->showOnShow(false)->showOnMcp(true),
    ];
}
```

### 3. Custom Tool Logic

```php
public function indexTool(array $arguments, McpRequest $request): array
{
    // Add default filters for MCP requests
    $arguments['status'] = $arguments['status'] ?? 'published';
    $arguments['visibility'] = 'public';
    
    $request->merge($arguments);
    
    return parent::indexTool($arguments, $request);
}
```

### 4. Error Handling

```php
public function showTool(array $arguments, McpRequest $request): array
{
    try {
        return parent::showTool($arguments, $request);
    } catch (ModelNotFoundException $e) {
        return [
            'error' => 'Resource not found',
            'message' => 'The requested resource does not exist or is not accessible.',
        ];
    }
}
```

## Field Serialization for AI Agents

One of the key challenges when working with AI agents is **token consumption**. Every field sent to an AI agent consumes tokens, and complex API responses can quickly exhaust token budgets. Laravel Restify's MCP integration provides powerful field serialization control to optimize token usage while maintaining functionality.

### The Token Usage Challenge

When AI agents interact with your API, they process every field in the response. Consider a typical blog post response:

```json
{
  "id": 123,
  "title": "My Blog Post",
  "content": "Lorem ipsum dolor sit amet...",
  "author_name": "John Doe", 
  "author_email": "john@example.com",
  "author_bio": "John is a developer...",
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-16T15:45:00Z",
  "published_at": "2024-01-15T12:00:00Z",
  "view_count": 1247,
  "like_count": 89,
  "comment_count": 23,
  "share_count": 45,
  "category": "Technology",
  "tags": ["php", "laravel", "api"],
  "featured_image_url": "https://example.com/image.jpg",
  "slug": "my-blog-post",
  "status": "published",
  "meta_description": "A comprehensive guide...",
  "meta_keywords": "php,laravel,tutorial",
  "reading_time": 5,
  "internal_notes": "Editor review needed",
  "moderation_status": "approved"
}
```

This response contains **~150 tokens**. For an AI agent listing 50 blog posts, that's **7,500 tokens** just for the data transfer, before any processing.

### MCP-Specific Field Methods

Laravel Restify allows you to define **operation-specific field sets** for MCP requests, dramatically reducing token usage:

```php
use Binaryk\LaravelRestify\MCP\Concerns\McpTools;

class PostRepository extends Repository
{
    use McpTools;
    
    // Regular API gets complete field set
    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('content'),
            Field::make('author_name'),
            Field::make('author_email'),
            Field::make('created_at'),
            Field::make('updated_at'),
            Field::make('view_count'),
            Field::make('comment_count'),
            Field::make('category'),
            Field::make('tags'),
            Field::make('slug'),
            Field::make('status'),
        ];
    }

    // MCP index: minimal fields for listing (saves ~70% tokens)
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('author_name'),
            Field::make('created_at'),
            Field::make('category'),
        ];
    }

    // MCP show: focused fields for detailed view (saves ~40% tokens)
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('content'), 
            Field::make('author_name'),
            Field::make('created_at'),
            Field::make('category'),
            Field::make('tags'),
            
            // AI-specific metadata (not in regular API)
            Field::make('reading_time'),
            Field::make('content_type'),
            Field::make('topic_classification'),
        ];
    }
}
```

### Available MCP Field Methods

Laravel Restify provides MCP-specific field methods for each operation type:

#### Core CRUD Operations

```php
public function fieldsForMcpIndex(RestifyRequest $request): array
{
    // Fields for listing operations (users-index-tool)
    // Focus: Essential identification and summary data
    return [
        Field::make('id'),
        Field::make('title'),
        Field::make('status'),
    ];
}

public function fieldsForMcpShow(RestifyRequest $request): array  
{
    // Fields for detailed view (users-show-tool)
    // Focus: Complete but optimized data set
    return [
        Field::make('title'),
        Field::make('content'),
        Field::make('metadata'),
    ];
}

public function fieldsForMcpStore(RestifyRequest $request): array
{
    // Fields for creation operations (users-store-tool) 
    // Focus: Required and optional input fields
    return [
        Field::make('title')->required(),
        Field::make('content')->required(),
        Field::make('category')->optional(),
    ];
}

public function fieldsForMcpUpdate(RestifyRequest $request): array
{
    // Fields for update operations (users-update-tool)
    // Focus: Editable fields only
    return [
        Field::make('title'),
        Field::make('content'),
        Field::make('status'),
    ];
}
```

#### Bulk Operations

```php
public function fieldsForMcpStoreBulk(RestifyRequest $request): array
{
    // Fields for bulk creation (users-store-bulk-tool)
    // Focus: Minimal required fields for efficiency
    return [
        Field::make('title')->required(),
        Field::make('status')->default('draft'),
    ];
}

public function fieldsForMcpUpdateBulk(RestifyRequest $request): array
{
    // Fields for bulk updates (users-update-bulk-tool) 
    // Focus: Commonly updated fields
    return [
        Field::make('status'),
        Field::make('category'),
        Field::make('updated_at'),
    ];
}
```

#### Getter Operations

```php  
public function fieldsForMcpGetter(RestifyRequest $request): array
{
    // Fields for custom getters (analytics-getter-tool)
    // Focus: Computed and analytical data
    return [
        Field::make('title'),
        Field::make('performance_score'),
        Field::make('engagement_metrics'), 
        Field::make('trend_analysis'),
    ];
}
```

### Token Usage Optimization Examples

#### Example 1: Blog Post Optimization

```php
class PostRepository extends Repository
{
    // Before: All fields (150 tokens per post)
    public function fields(RestifyRequest $request): array
    {
        return [
            Field::make('id'),
            Field::make('title'),
            Field::make('content'),
            Field::make('author_name'),
            Field::make('author_email'),
            Field::make('author_bio'),
            Field::make('created_at'),
            Field::make('updated_at'), 
            Field::make('published_at'),
            Field::make('view_count'),
            Field::make('like_count'),
            Field::make('comment_count'),
            Field::make('category'),
            Field::make('tags'),
            Field::make('slug'),
            Field::make('status'),
            Field::make('meta_description'),
            Field::make('reading_time'),
        ];
    }

    // After: Optimized for index (45 tokens per post - 70% savings!)
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('author_name'),
            Field::make('created_at'),
            Field::make('category'),
            Field::make('reading_time'),
        ];
    }

    // After: Optimized for show (90 tokens per post - 40% savings!)
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('content'),
            Field::make('author_name'), 
            Field::make('created_at'),
            Field::make('category'),
            Field::make('tags'),
            Field::make('reading_time'),
            
            // AI-specific fields not needed by humans
            Field::make('content_complexity'),
            Field::make('topic_classification'),
            Field::make('sentiment_score'),
        ];
    }
}
```

**Token Savings:**
- **Index operation**: 50 posts × (150 - 45) = **5,250 tokens saved** 
- **Show operation**: (150 - 90) = **60 tokens saved per view**

#### Example 2: User Profile Optimization

```php
class UserRepository extends Repository  
{
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        // Minimal user listing for AI
        return [
            Field::make('name'),
            Field::make('role'), 
            Field::make('status'),
            Field::make('last_active_at'),
        ];
    }

    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        // Comprehensive user profile for AI analysis
        return [
            Field::make('name'),
            Field::make('email'),
            Field::make('role'),
            Field::make('status'),
            Field::make('created_at'),
            Field::make('last_active_at'),
            
            // AI-helpful metadata
            Field::make('activity_score'),
            Field::make('engagement_level'),
            Field::make('expertise_areas'),
            Field::make('interaction_patterns'),
        ];
    }
}
```

### Field Method Priority System

Laravel Restify uses a **hierarchical field method resolution system** with the following priority order:

1. **MCP-specific methods** (highest priority)
   - `fieldsForMcpIndex()`, `fieldsForMcpShow()`, etc.
   - Used when request is `McpRequest` and method exists

2. **Request-specific methods** (fallback)
   - `fieldsForIndex()`, `fieldsForShow()`, etc.  
   - Used when MCP method doesn't exist

3. **Base fields method** (default)
   - `fields()` 
   - Used when no specific method exists

```php
class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        // Default fields for all requests
        return [Field::make('title'), Field::make('content')];
    }

    public function fieldsForIndex(RestifyRequest $request): array
    {
        // Regular API index fields  
        return [Field::make('title'), Field::make('author')];
    }

    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        // MCP-specific index fields (highest priority)
        return [Field::make('title'), Field::make('summary')];
    }
}

// Request resolution:
// Regular index request -> uses fieldsForIndex()
// MCP index request -> uses fieldsForMcpIndex() 
// MCP show request -> uses fields() (no fieldsForMcpShow defined)
```

### Advanced Usage Patterns

#### Conditional Field Inclusion

```php
public function fieldsForMcpShow(RestifyRequest $request): array
{
    $fields = [
        Field::make('title'),
        Field::make('content'),
    ];

    // Add admin-only fields for authorized users
    if ($request->user()?->can('viewAdminData', static::$model)) {
        $fields[] = Field::make('internal_notes');
        $fields[] = Field::make('moderation_queue_position');
        $fields[] = Field::make('performance_analytics');
    }

    // Add debug fields in development
    if (app()->environment('local')) {
        $fields[] = Field::make('debug_sql_queries');
        $fields[] = Field::make('cache_hit_ratio');
    }

    return $fields;
}
```

#### AI-Specific Computed Fields

```php
public function fieldsForMcpIndex(RestifyRequest $request): array
{
    return [
        Field::make('title'),
        Field::make('author'),
        
        // Computed fields specifically for AI understanding
        Field::make('content_summary')->resolveUsing(function ($post) {
            return Str::limit(strip_tags($post->content), 100);
        }),
        
        Field::make('readability_score')->resolveUsing(function ($post) {
            return app(ReadabilityAnalyzer::class)->score($post->content);
        }),
        
        Field::make('topic_keywords')->resolveUsing(function ($post) {
            return app(KeywordExtractor::class)->extract($post->content);
        }),
    ];
}
```

#### Different Field Sets Per Operation

```php
class ProductRepository extends Repository
{
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        // Minimal product listing for browsing
        return [
            Field::make('name'),
            Field::make('price'),
            Field::make('category'),
            Field::make('availability'),
        ];
    }

    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        // Detailed product info for decision-making
        return [
            Field::make('name'),
            Field::make('description'),
            Field::make('price'),
            Field::make('specifications'),
            Field::make('reviews_summary'),
            Field::make('stock_level'),
            Field::make('related_products'),
        ];
    }

    public function fieldsForMcpStore(RestifyRequest $request): array
    {
        // Required fields for product creation
        return [
            Field::make('name')->required(),
            Field::make('price')->required(),
            Field::make('category')->required(),
            Field::make('description')->optional(),
            Field::make('specifications')->optional(),
        ];
    }

    public function fieldsForMcpGetter(RestifyRequest $request): array
    {
        // Analytics data for reporting
        return [
            Field::make('name'),
            Field::make('sales_velocity'),
            Field::make('conversion_rate'),
            Field::make('profit_margin'),
            Field::make('competitor_analysis'),
        ];
    }
}
```

### Integration with Field Visibility Controls

MCP field methods work seamlessly with Laravel Restify's existing field visibility system:

```php
public function fieldsForMcpShow(RestifyRequest $request): array
{
    return [
        Field::make('title'),
        Field::make('content'),
        
        // This field will be included in the MCP field set
        // but still respects hideFromMcp() rules 
        Field::make('admin_notes')->hideFromMcp(function($request) {
            return !$request->user()?->isAdmin();
        }),
        
        // This field is MCP-only (hidden from regular API)
        Field::make('ai_processing_metadata')
            ->showOnIndex(false)
            ->showOnShow(false)
            ->showOnMcp(true),
    ];
}
```

**Field Resolution Order:**
1. Field is included in `fieldsForMcpShow()` method
2. Field visibility rules (`hideFromMcp()`, `showOnMcp()`) are applied
3. Final field set is returned to AI agent

### Performance and Token Considerations

#### Token Usage Guidelines

| Operation Type | Recommended Field Count | Token Estimate |
|----------------|------------------------|----------------|
| Index | 3-5 fields | 20-40 tokens per item |
| Show | 8-12 fields | 60-100 tokens per item |  
| Store/Update | 5-8 fields | 30-60 tokens per item |
| Getter | 4-10 fields | 25-75 tokens per item |

#### Performance Best Practices

```php
public function fieldsForMcpIndex(RestifyRequest $request): array
{
    return [
        // ✅ Good: Essential fields only
        Field::make('title'),
        Field::make('status'), 
        Field::make('created_at'),

        // ❌ Avoid: Heavy computed fields in index
        // Field::make('complex_calculation')->resolveUsing(fn($model) => 
        //     $this->heavyComputation($model)
        // ),

        // ✅ Good: Simple computed fields
        Field::make('summary')->resolveUsing(fn($model) => 
            Str::limit($model->content, 50)
        ),
    ];
}
```

#### Memory Usage Optimization

```php
public function fieldsForMcpIndex(RestifyRequest $request): array
{
    // Avoid loading unnecessary relationships
    return [
        Field::make('title'),
        Field::make('author_name'), // ✅ Direct attribute
        // Field::make('author.profile.bio'), // ❌ Deep relationship
    ];
}
```

### Migration Guide for Existing Repositories

#### Step 1: Analyze Current Token Usage

```php
// Before: Count tokens in existing response
$response = $this->get('/api/posts');
$tokenCount = $this->estimateTokens($response->json());
```

#### Step 2: Add MCP Field Methods Gradually

```php
class PostRepository extends Repository
{
    use McpTools; // Add if not already present

    // Start with index optimization (biggest impact)
    public function fieldsForMcpIndex(RestifyRequest $request): array
    {
        return [
            Field::make('title'),
            Field::make('author_name'),
            Field::make('created_at'),
        ];
    }

    // Add show optimization next
    public function fieldsForMcpShow(RestifyRequest $request): array
    {
        // Include essential fields + AI-helpful metadata
        return array_merge(
            $this->getEssentialFields(),
            $this->getAiMetadataFields()
        );
    }

    private function getEssentialFields(): array
    {
        return [
            Field::make('title'),
            Field::make('content'),
            Field::make('author_name'),
        ];
    }

    private function getAiMetadataFields(): array
    {
        return [
            Field::make('reading_time'),
            Field::make('complexity_score'),
            Field::make('topic_tags'),
        ];
    }
}
```

#### Step 3: Test and Measure

```php
// Test MCP field methods
$mcpRequest = new McpRequest(['params' => ['name' => 'posts-index-tool']]);
$fields = $repository->collectFields($mcpRequest);
$tokenCount = $this->estimateTokens($fields->toArray());

// Compare with regular request
$regularRequest = new RestifyRequest();
$regularFields = $repository->collectFields($regularRequest);
$regularTokenCount = $this->estimateTokens($regularFields->toArray());

$savings = ($regularTokenCount - $tokenCount) / $regularTokenCount * 100;
echo "Token savings: {$savings}%";
```

### Best Practices Summary

#### 🎯 **Token Optimization Strategies**

1. **Start Minimal**: Begin with 3-5 essential fields for index operations
2. **Measure Impact**: Track token usage before and after optimization  
3. **Add AI-Specific Fields**: Include fields that help AI understanding
4. **Avoid Redundancy**: Don't duplicate information across fields
5. **Use Computed Fields Wisely**: Simple calculations OK, avoid heavy operations

#### 📋 **Field Selection Guidelines**

- **Index Operations**: Identity, status, and summary fields only
- **Show Operations**: Complete but focused dataset for decision-making  
- **Store/Update**: Only fields relevant to the specific operation
- **Getter Operations**: Analytics, metrics, and computed data

#### ⚡ **Performance Considerations**

- Monitor memory usage with large datasets
- Optimize database queries and relationship loading
- Cache expensive computed fields
- Use pagination appropriately for MCP requests
- Test with production-sized datasets

#### 🔒 **Security Best Practices** 

- Apply field visibility controls (`hideFromMcp()`) for sensitive data
- Use conditional field inclusion based on user permissions
- Audit MCP field access patterns
- Implement rate limiting for token-heavy operations

## Configuration

The MCP integration respects your existing Restify configuration and adds MCP-specific options:

```php
// config/restify.php
'mcp' => [
    'enabled' => true,
    'server_name' => 'My App MCP Server',
    'server_version' => '1.0.0',
    'default_pagination' => 25,
    'tools' => [
        'exclude' => [
            // Tools to exclude from discovery
        ],
        'include' => [
            // Additional tools to include
        ],
    ],
],
```