---
title: MCP Repositories
menuTitle: Repositories
category: MCP
position: 2
---

Laravel Restify repositories provide first-class support for Model Context Protocol (MCP), enabling AI agents to efficiently interact with your APIs. This page covers MCP-specific repository features that optimize token usage and provide tailored data structures for AI consumption.

## Enabling MCP Tools

To provide MCP server access to your repository, you must include the `HasMcpTools` trait. By default, this trait provides an index tool for AI agents:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('title')->required()->searchable()->sortable(),
            field('content')->string(),
            field('status')->matchable(),
            field('category')->matchable(),
        ];
    }
    
    public static function related(): array
    {
        return [
            'author' => BelongsTo::make('user', UserRepository::class),
            'comments' => HasMany::make('comments', CommentRepository::class), 
            'tags' => BelongsToMany::make('tags', TagRepository::class),
        ];
    }
}
```

**Generated MCP Index Tool:**

When you include the `HasMcpTools` trait, Restify automatically generates an index tool. For example, with the post repository above that has matchable filters, the generated tool looks like this:

```json
{
    "jsonrpc": "2.0",
    "id": 2,
    "result": {
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
                        "include": {
                            "type": "string",
                            "description": "Comma-separated list of relationships to include with optional field selection.\n\nAvailable relationships:\n- author (fields: name)\n- comments (fields: content)\n- tags (fields: color, name)\n\nField Selection:\nYou can specify which fields to include for each relationship using square brackets.\nSyntax: relationship[field1|field2]\n\nNested Relationships:\nYou can include deeply nested relationships using dot notation with field selection at each level.\nSyntax: relationship[fields].nested[fields].deeper[fields] - supports unlimited nesting depth\nNote: Field selection works at every nesting level independently.\n\nExamples:\n- include=author,comments (include all fields)\n- include=author[name] (selective fields with comma syntax)\n- include=author[name] (selective fields with pipe syntax)\n- include=author.posts (nested relationship - all fields)\n- include=author[name].posts[title] (nested with field selection at each level)\n- include=author[name|email].posts[title].tags[id] (deep nesting - 3 levels with field selection)\n- include=author.posts[title],author.comments[body] (multiple nested from same parent)\n- include=author[name],tags[id] (multiple relationships with field selection)\n- include=author[email|name].posts[title],tags[id] (mixing deep nested and simple relationships)"
                        },
                        "search": {
                            "type": "string",
                            "description": "Search term to filter posts by title or description. Available searchable fields: title (e.g., search=term)"
                        },
                        "sort": {
                            "type": "string",
                            "description": "Sorting criteria for the posts. Available options: posts.id, title (e.g., sort=field or sort=-field for descending)"
                        },
                        "status": {
                            "type": "string",
                            "description": "Filter posts resource. Description: This is a exact match for status (e.g., status=published). It accepts negation by prefixing the column with a hyphen (e.g., -status=draft). The filter type is string."
                        },
                        "category": {
                            "type": "string",
                            "description": "Filter posts resource. Description: This is a exact match for category (e.g., category=technology). It accepts negation by prefixing the column with a hyphen (e.g., -category=news). The filter type is string."
                        }
                    },
                    "required": []
                },
                "annotations": {}
            }
        ]
    }
}
```

Notice how the generated tool automatically includes:
- Pagination parameters (`page`, `perPage`)
- Relationship inclusion with field selection syntax
- Search capabilities based on searchable fields (title)
- Sort options from sortable fields (title)
- Filter parameters for each matchable field (status, category)

## Repository Description for AI Agents

Providing a clear description of your repository helps AI agents understand its purpose, domain, and capabilities. You can customize the repository description by setting the static `$description` property or by overriding the static `description()` method.

### Using the Static Property

The simplest way to provide a repository description is by setting the `$description` property:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;

    public static string $description = 'Manages blog posts including articles, tutorials, and news. Posts can be published, drafted, or scheduled for future publication. Each post belongs to an author and can have multiple tags and categories.';

    //...
}
```

### Using the Description Method

For dynamic descriptions or when you need more control, override the static `description()` method:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;

    public static function description(RestifyRequest $request): string
    {
        $userRole = $request->user()?->role;

        if ($userRole === 'admin') {
            return 'Manages all blog posts with full administrative access. Posts can be created, edited, published, or deleted. Includes moderation tools and analytics.';
        }

        return 'Manages blog posts for content creators. Authors can create and edit their own posts, submit for review, and view publication status.';
    }

    //...
}
```

### Default Auto-Generated Description

If you don't provide a custom description, Restify automatically generates one based on your model structure:

```php
// For a Post model with table 'posts' and fillable fields: ['title', 'content', 'status', 'author_id']
// Auto-generated description:
"This repository manages the [Post] model, which corresponds to the [posts] table in the database.
It provides functionalities such as listing, searching, sorting, filtering, and relationship management.
The model/table has the following attributes: title, content, status, author_id."
```

### Best Practices for Repository Descriptions

1. **Be specific about the domain**: Explain what the repository manages in business terms
2. **Mention key capabilities**: Highlight special features like publishing workflows, approval processes, or calculations
3. **Include context**: Describe relationships and dependencies that AI agents should know about
4. **Keep it concise**: Aim for 2-3 sentences that provide clear context without overwhelming detail

**Good Example:**
```php
public static string $description = 'Manages user subscriptions and billing. Handles subscription creation, upgrades, downgrades, cancellations, and payment processing through Stripe. Each subscription is linked to a user and a pricing plan.';
```

**Poor Example:**
```php
public static string $description = 'CRUD operations for subscriptions.'; // Too vague
```

This description will be used by AI agents when deciding which tools to use and how to interact with your API, making it crucial for effective AI integration.

## Configuring MCP Tools

By default, the `HasMcpTools` trait only enables the **index** tool. To enable other tools like show, store, update, or delete, you need to override the corresponding methods in your repository:

```php
use Binaryk\LaravelRestify\Traits\HasMcpTools;

#[Model(Post::class)]
class PostRepository extends Repository
{
    use HasMcpTools;
    
    // Index tool is enabled by default
    public function mcpAllowsIndex(): bool
    {
        return true; // Default: true
    }
    
    // Enable show tool for AI agents
    public function mcpAllowsShow(): bool
    {
        return true; // Default: false
    }
    
    // Enable store (create) tool for AI agents
    public function mcpAllowsStore(): bool
    {
        return true; // Default: false
    }
    
    // Enable update tool for AI agents
    public function mcpAllowsUpdate(): bool
    {
        return true; // Default: false
    }
    
    // Enable delete tool for AI agents
    public function mcpAllowsDelete(): bool
    {
        return true; // Default: false
    }
    
    // Enable action tools for AI agents
    public function mcpAllowsActions(): bool
    {
        return true; // Default: false
    }
    
    // Enable getter tools for AI agents
    public function mcpAllowsGetters(): bool
    {
        return true; // Default: false
    }
}
```

### Conditional Tool Access

You can conditionally enable tools based on user permissions or other criteria:

```php
class PostRepository extends Repository
{
    use HasMcpTools;
    
    public function mcpAllowsShow(): bool
    {
        return true; // Allow all authenticated users to view posts
    }
    
    public function mcpAllowsStore(): bool
    {
        // Only allow content creators to create posts via AI
        return request()->user()?->hasPermissionTo('create-posts') ?? false;
    }
    
    public function mcpAllowsUpdate(): bool
    {
        // Only allow editors to update posts via AI
        return request()->user()?->hasRole('editor') ?? false;
    }
    
    public function mcpAllowsDelete(): bool
    {
        // Only allow admins to delete posts via AI
        return request()->user()?->hasRole('admin') ?? false;
    }
    
    public function mcpAllowsActions(): bool
    {
        // Enable custom actions for power users
        return request()->user()?->hasRole(['admin', 'editor']) ?? false;
    }
}
```

## Tool Security

### Default Security Approach

Restify takes a **secure by default** approach:
- Only the **index** tool is enabled by default
- All other tools (show, store, update, delete) are **disabled** by default
- You must explicitly enable each tool you want AI agents to access
- Authorization policies still apply to all MCP requests

### Best Practices for Tool Access

```php
class PostRepository extends Repository
{
    use HasMcpTools;
    
    public function mcpAllowsShow(): bool
    {
        // Safe: Reading data is generally low risk
        return true;
    }
    
    public function mcpAllowsStore(): bool
    {
        // Moderate risk: Consider user permissions
        return request()->user()?->can('create', Post::class) ?? false;
    }
    
    public function mcpAllowsUpdate(): bool
    {
        // Higher risk: Require explicit permissions
        return request()->user()?->hasPermissionTo('ai-edit-posts') ?? false;
    }
    
    public function mcpAllowsDelete(): bool
    {
        // Highest risk: Very restrictive
        return request()->user()?->hasRole('super-admin') ?? false;
    }
}
```

## Field Optimization for AI Agents

For detailed information about optimizing field responses for AI agents, including MCP-specific field methods, token optimization, and conditional field visibility, see the **[MCP Fields](/mcp/fields)** documentation.


## MCP Tool Examples

Laravel Restify automatically generates MCP tools for AI agents based on your repository configuration. Here's what the tools look like from an AI agent's perspective:

### Index Tool Example

When you define a repository with fields and relationships, Restify generates an index tool:

**Repository:**
```php
#[Model(Organization::class)]
class OrganizationRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->searchable()->sortable(),
            field('address')->matchable(),
            field('city')->matchable(),
            field('country')->matchable(),
        ];
    }
    
    public static function related(): array
    {
        return [
            'users' => HasMany::make('users', UserRepository::class),
            'teams' => HasMany::make('teams', TeamRepository::class), 
            'tags' => BelongsToMany::make('tags', TagRepository::class),
        ];
    }
}
```

**Generated MCP Tool:**
```json
{
    "jsonrpc": "2.0",
    "id": 2,
    "result": {
        "tools": [
            {
                "name": "organizations-index-tool",
                "description": "Retrieve a paginated list of Organization records from the organizations repository with filtering, sorting, and search capabilities.",
                "inputSchema": {
                    "type": "object",
                    "properties": {
                        "page": {
                            "type": "number",
                            "description": "Page number for pagination"
                        },
                        "perPage": {
                            "type": "number",
                            "description": "Number of organizations per page"
                        },
                        "include": {
                            "type": "string",
                            "description": "Comma-separated list of relationships to include with optional field selection.\n\nAvailable relationships:\n- users (fields: name)\n- teams (fields: name)\n- tags (fields: color, name)\n\nField Selection:\nYou can specify which fields to include for each relationship using square brackets.\nSyntax: relationship[field1|field2]\n\nNested Relationships:\nYou can include deeply nested relationships using dot notation with field selection at each level.\nSyntax: relationship[fields].nested[fields].deeper[fields] - supports unlimited nesting depth\nNote: Field selection works at every nesting level independently.\n\nExamples:\n- include=users,teams (include all fields)\n- include=users[name] (selective fields with comma syntax)\n- include=users[name] (selective fields with pipe syntax)\n- include=users.posts (nested relationship - all fields)\n- include=users[name].posts[title] (nested with field selection at each level)\n- include=users[name|email].posts[title].tags[id] (deep nesting - 3 levels with field selection)\n- include=users.posts[title],users.comments[body] (multiple nested from same parent)\n- include=users[name],teams[id] (multiple relationships with field selection)\n- include=users[email|name].posts[title],teams[id] (mixing deep nested and simple relationships)"
                        },
                        "search": {
                            "type": "string",
                            "description": "Search term to filter organizations by name or description. Available searchable fields: name (e.g., search=term)"
                        },
                        "sort": {
                            "type": "string",
                            "description": "Sorting criteria for the organizations. Available options: organizations.id, name (e.g., sort=field or sort=-field for descending)"
                        },
                        "tags": {
                            "type": "string",
                            "description": "Filter organizations resource. This is an exact match for tags (e.g., tags=some_value). It accepts negation by prefixing the column with a hyphen (e.g., -tags=some_value). The filter type is string."
                        },
                        "address": {
                            "type": "string",
                            "description": "Filter organizations resource. This is an exact match for address (e.g., address=some_value). It accepts negation by prefixing the column with a hyphen (e.g., -address=some_value). The filter type is string."
                        },
                        "city": {
                            "type": "string",
                            "description": "Filter organizations resource. This is an exact match for city (e.g., city=some_value). It accepts negation by prefixing the column with a hyphen (e.g., -city=some_value). The filter type is string."
                        },
                        "country": {
                            "type": "string",
                            "description": "Filter organizations resource. This is an exact match for country (e.g., country=some_value). It accepts negation by prefixing the column with a hyphen (e.g., -country=some_value). The filter type is string."
                        }
                    }
                }
            }
        ]
    }
}
```

### Show Tool Example

**Generated Show Tool:**
```json
{
    "name": "organizations-show-tool",
    "description": "Retrieve a specific Organization record by ID with optional relationship loading.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "id": {
                "type": "string",
                "description": "The ID of the organization to retrieve"
            },
            "include": {
                "type": "string",
                "description": "Comma-separated list of relationships to include (e.g., include=users,teams,tags)"
            }
        },
        "required": ["id"]
    }
}
```

### Store Tool Example

**Generated Store Tool:**
```json
{
    "name": "posts-store-tool",
    "description": "Create a new Post record.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "title": {
                "type": "string",
                "description": "Title field for posts"
            },
            "content": {
                "type": "string",
                "description": "Content field for posts"
            },
            "status": {
                "type": "string", 
                "description": "Status field for posts"
            },
            "category": {
                "type": "string",
                "description": "Category field for posts"
            }
        },
        "required": [
            "title"
        ]
    }
}
```

### Update Tool Example

**Generated Update Tool:**
```json
{
    "name": "posts-update-tool",
    "description": "Update an existing Post record by ID.",
    "inputSchema": {
        "type": "object",
        "properties": {
            "id": {
                "type": "string",
                "description": "The ID of the post to update"
            },
            "title": {
                "type": "string",
                "description": "Title field for posts"
            },
            "content": {
                "type": "string",
                "description": "Content field for posts"
            },
            "status": {
                "type": "string",
                "description": "Status field for posts"
            },
            "category": {
                "type": "string", 
                "description": "Category field for posts"
            }
        },
        "required": [
            "id"
        ]
    }
}
```

### Complete Tool Set

For a typical repository, Restify generates these MCP tools:

- **`{resource}-index-tool`** - List/search records with pagination and filtering
- **`{resource}-show-tool`** - Get a specific record by ID
- **`{resource}-store-tool`** - Create new records
- **`{resource}-update-tool`** - Update existing records
- **`{resource}-destroy-tool`** - Delete records (if authorized)

### Tool Features Generated from Fields

**Field Modifiers → Tool Properties:**

```php
field('name')->searchable()     // → adds to "search" description
field('status')->matchable()    // → adds "status" filter parameter  
field('title')->sortable()      // → adds to "sort" options
field('email')->required()      // → adds to "required" array in schema
```

**Relationships → Include Options:**

```php
HasMany::make('posts', PostRepository::class)     // → "posts" in include options
BelongsTo::make('author', UserRepository::class)  // → "author" in include options
```

**Validation Rules → Schema:**

```php
field('email')->rules('email')           // → type: "string", format: "email"
field('age')->rules('integer', 'min:18') // → type: "integer", minimum: 18
field('status')->rules('in:draft,published') // → enum: ["draft", "published"]
```

This automatic tool generation means you define your API once in the repository, and AI agents get a complete, type-safe interface with detailed documentation and examples.

This MCP repository system allows you to provide highly optimized APIs for AI agents while maintaining full functionality for human users, all from a single, unified codebase.
