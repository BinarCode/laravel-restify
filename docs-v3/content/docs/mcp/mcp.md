---
title: Model Context Protocol (MCP)
menuTitle: MCP
category: MCP
position: 1
---

Laravel Restify provides seamless integration with the Model Context Protocol (MCP), allowing AI agents to interact with your REST API resources through structured tool interfaces. So you can simply tranform your repositories into a tools for AI agents to consume. Incredible!

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

Mcp::web('restify', RestifyServer::class)->middleware([
    'auth:sanctum',
])->name('mcp.restify');
```

And that's it! Now you can access your Restify API through the MCP server with authentication. Go into n8n or your AI agent of choice and connect to the MCP server endpoint.

### Terminal/STDIN Access (Local MCP)

For terminal-based AI agents (like Claude Desktop, cursor, or other CLI tools that support MCP), you can expose your Restify API through STDIN/STDOUT using the `local` syntax. This allows direct integration without HTTP overhead.

#### Registering a Local MCP Server

Register your local MCP server in the `routes/ai.php` file:

```php
use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\GroweeStdServer;

// Register for terminal/STDIN access
Mcp::local('growee', GroweeStdServer::class);
```

#### Creating a Terminal-Accessible Server with Authentication

When using terminal access, authentication must be handled within the server's `boot()` method since there's no HTTP middleware pipeline. Here's a complete example that extends RestifyServer and implements Sanctum authentication:

```php
<?php

namespace App\Mcp\Servers;

use Binaryk\LaravelRestify\MCP\RestifyServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Mcp\Server\Exceptions\McpException;
use Laravel\Sanctum\PersonalAccessToken;

class GroweeStdServer extends RestifyServer
{
    public function boot(): void
    {
        $request = request();

        // Get the API token from Authorization header
        $bearerToken = $request->bearerToken();

        // Fail if no API key is provided
        if (! $bearerToken) {
            throw new McpException('API key is required. Please provide a Bearer token in the Authorization header.');
        }

        // Try to authenticate using Sanctum
        $token = PersonalAccessToken::findToken($bearerToken);

        if (! $token) {
            throw new McpException('Invalid API key provided. Please check your Bearer token.');
        }

        // Verify the token is active
        if (! $token->tokenable) {
            throw new McpException('API token is not associated with a valid user.');
        }

        // Set the authenticated user on both sanctum and default guard
        Auth::guard('sanctum')->setUser($token->tokenable);
        Auth::setUser($token->tokenable);

        // Set the user resolver for the request
        $request->setUserResolver(function () use ($token) {
            return $token->tokenable;
        });
        
        // Call parent boot to discover tools, resources, and prompts
        parent::boot();
    }
}
```

#### Key Differences from Web Access

1. **No Middleware Pipeline**: Unlike `Mcp::web()`, the `local` syntax doesn't support middleware. All authentication and authorization must be implemented in the `boot()` method.

2. **Direct API Token**: Terminal clients provide Bearer tokens directly through the Authorization header.

3. **Request Context**: Access to the request is available via the `request()` helper function.

4. **Error Handling**: Use `McpException` for authentication failures to provide clear error messages to the terminal client.

5. **Registration Location**: Local MCP servers are typically registered in `routes/ai.php` instead of web routes.

#### Client Configuration

Terminal-based AI clients (like Claude Desktop) can connect to your local MCP server by configuring the connection with your API token. The exact configuration depends on your client, but typically involves:

1. Setting the Authorization header with your Sanctum token
2. Specifying the server name (e.g., 'growee')
3. Pointing to your Laravel application's MCP endpoint

#### Security Considerations

- **Token Security**: API tokens are passed via the Authorization header and should be kept secure
- **Token Scopes**: Consider implementing token abilities/scopes to limit access
- **Rate Limiting**: Implement rate limiting at the application level since middleware isn't available
- **Audit Logging**: Log authentication attempts and API usage for security monitoring
- **Token Rotation**: Implement token expiration and rotation policies

#### 🔒 **Security Best Practices** 

- Apply field visibility controls (`hideFromMcp()`) for sensitive data
- Audit MCP field access patterns
- Implement rate limiting for token-heavy operations

## Common Issues

### Schema Validation Error

**Error**: `[ERROR: Received tool input did not match expected schema]`

**Cause**: This occurs when the field type is not identified correctly by the MCP server, leading to schema mismatches between what the AI agent sends and what Laravel Restify expects.

**Solution**: You need to explicitly override the field type for the MCP schema using the `toolSchema()` method:

```php
field('project_id')
    ->toolSchema(function(\Laravel\Mcp\Server\Tools\ToolInputSchema $schema) {
        $schema->string('project_id')
            ->description('The ID of the project associated with the timesheet entry.')
            ->required();
    }),
```

This approach allows you to:
- Explicitly define the expected data type (string, integer, boolean, etc.)
- Add detailed descriptions for AI agents
- Set validation rules (required, optional)
- Override automatic type inference when it's incorrect

## Configuration

The MCP integration respects your existing Restify configuration and adds MCP-specific options:

```php [config/restify.php]
'mcp' => [
    'enabled' => true,
    'server_name' => 'My App MCP Server',
    'server_version' => '1.0.0',
    'default_pagination' => 25,
    'mode' => env('RESTIFY_MCP_MODE', 'direct'), // 'direct' or 'wrapper'
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

## MCP Mode: Direct vs Wrapper

Laravel Restify offers two modes for exposing your repositories through MCP: **Direct Mode** and **Wrapper Mode**. Each mode has different trade-offs in terms of token usage and discoverability.

### Direct Mode (Default)

In direct mode, every repository operation (index, show, store, update, delete) and custom action/getter is exposed as a separate MCP tool. This provides maximum discoverability for AI agents.

**When to use Direct Mode:**
- You have a small number of repositories (< 10)
- You want AI agents to instantly see all available operations
- Token usage is not a concern
- You prefer simpler, more straightforward tool discovery

**Example:** With 10 repositories, each having 5 CRUD operations plus 2 actions, you would expose **70 tools** to the AI agent.

**Configuration:**
```php
// .env
RESTIFY_MCP_MODE=direct
```

### Wrapper Mode (Token-Efficient)

Wrapper mode uses a progressive discovery pattern that exposes only **4 wrapper tools** regardless of how many repositories you have. AI agents discover and execute operations through a multi-step workflow.

**When to use Wrapper Mode:**
- You have many repositories (10+)
- Token usage efficiency is important (e.g., working with large context windows)
- You want to reduce the initial tool list size
- You're building complex applications with dozens of repositories

**Token Savings Example:**
- Direct mode with 50 repositories: ~250+ tools exposed
- Wrapper mode with 50 repositories: **4 tools exposed**
- **Token reduction: ~98% fewer tokens used for tool definitions**

**Configuration:**
```php
// .env
RESTIFY_MCP_MODE=wrapper
```

### The 4 Wrapper Tools

When using wrapper mode, AI agents use these 4 tools in a progressive discovery workflow:

#### 1. `discover-repositories`
Lists all available MCP-enabled repositories with metadata. Supports optional search filtering.

**Example Request:**
```json
{
  "search": "user"
}
```

**Example Response:**
```json
{
  "success": true,
  "repositories": [
    {
      "name": "users",
      "title": "Users",
      "description": "Manage user accounts",
      "operations": ["index", "show", "store", "update", "delete", "profile"],
      "actions_count": 2,
      "getters_count": 1
    }
  ]
}
```

#### 2. `get-repository-operations`
Lists all operations, actions, and getters available for a specific repository.

**Example Request:**
```json
{
  "repository": "users"
}
```

**Example Response:**
```json
{
  "success": true,
  "repository": "users",
  "crud_operations": ["index", "show", "store", "update", "delete", "profile"],
  "actions": [
    {
      "name": "activate-user",
      "title": "Activate User",
      "description": "Activate a user account"
    }
  ],
  "getters": [
    {
      "name": "active-users",
      "title": "Active Users",
      "description": "Get all active users"
    }
  ]
}
```

#### 3. `get-operation-details`
Returns the complete JSON schema and documentation for a specific operation, including all parameters, validation rules, and examples.

**Example Request:**
```json
{
  "repository": "users",
  "operation_type": "store"
}
```

**Example Response:**
```json
{
  "success": true,
  "operation": "store",
  "type": "create",
  "title": "Create User",
  "description": "Create a new user account",
  "schema": {
    "type": "object",
    "properties": {
      "name": {
        "type": "string",
        "description": "The user's full name",
        "required": true
      },
      "email": {
        "type": "string",
        "description": "The user's email address",
        "required": true
      }
    }
  },
  "examples": [
    {
      "name": "John Doe",
      "email": "john@example.com"
    }
  ]
}
```

#### 4. `execute-operation`
Executes a repository operation with the provided parameters. This is the final step after discovering the repository, listing operations, and getting operation details.

**Example Request:**
```json
{
  "repository": "users",
  "operation_type": "store",
  "parameters": {
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

**Example Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

### Wrapper Mode Workflow

Here's a typical workflow when an AI agent uses wrapper mode:

1. **Discover repositories**: Agent calls `discover-repositories` to see what repositories are available
2. **Explore operations**: Agent calls `get-repository-operations` for the target repository
3. **Get schema**: Agent calls `get-operation-details` to understand required parameters
4. **Execute**: Agent calls `execute-operation` with the correct parameters

This progressive discovery pattern reduces token usage while maintaining full functionality.

### Switching Between Modes

You can switch between modes at any time by updating your `.env` file:

```bash
# Direct mode (default)
RESTIFY_MCP_MODE=direct

# Wrapper mode (token-efficient)
RESTIFY_MCP_MODE=wrapper
```

No code changes are required. The MCP server automatically adapts to the configured mode.

### Performance Considerations

**Direct Mode:**
- ✅ Faster initial discovery (all tools visible immediately)
- ❌ Higher token usage (all tools loaded into context)
- ✅ Simpler for AI agents to understand
- ❌ Can overwhelm context window with large applications

**Wrapper Mode:**
- ✅ Dramatically lower token usage (4 tools vs 100+)
- ✅ Scales well with large applications
- ❌ Requires multi-step workflow
- ✅ Better for applications with many repositories

### Best Practices

1. **Start with Direct Mode** during development to verify all tools are working correctly
2. **Switch to Wrapper Mode** in production if you have 10+ repositories or token efficiency is important
3. **Use wrapper mode** when working with AI agents that have limited context windows
4. **Monitor token usage** to determine which mode is best for your application
5. **Document your choice** so team members understand which mode is active

## Fine-Grained Tool Permissions

Laravel Restify's MCP integration includes a powerful permission system that allows you to control which tools each API token can access. This is essential for multi-tenant applications or when you need to restrict AI agent capabilities.

### How Permission Control Works

The `RestifyServer` class provides a `canUseTool()` method that is called whenever a tool is accessed. By default, this method returns `true` (all tools are accessible), but you can override it in your application server to implement custom permission logic.

**Key Behavior:**
- `canUseTool()` is called during **tool discovery** (what tools the AI agent sees)
- `canUseTool()` is called during **tool execution** (whether the operation is allowed)
- In **wrapper mode**, permissions are checked for individual operations, not just the 4 wrapper tools
- Tools without permission are completely hidden from the AI agent

### Implementing Token-Based Permissions

Create a custom MCP server that extends `RestifyServer` and implements permission checks:

```php
<?php

namespace App\Mcp;

use Binaryk\LaravelRestify\MCP\RestifyServer;
use App\Models\McpToken;

class ApplicationServer extends RestifyServer
{
    public function canUseTool(string|object $tool): bool
    {
        // Extract tool name from string or object
        $toolName = is_string($tool) ? $tool : $tool->name();

        // Get the API token from the request
        $bearerToken = request()->bearerToken();

        if (!$bearerToken) {
            return false;
        }

        // Find the MCP token record
        $mcpToken = McpToken::where('token', hash('sha256', $bearerToken))
            ->first();

        if (!$mcpToken) {
            return false;
        }

        // Check if this tool is in the token's allowed tools list
        // $mcpToken->allowed_tools is a JSON array like:
        // ["users-index", "posts-store", "posts-update-status-action"]
        return in_array($toolName, $mcpToken->allowed_tools ?? [], true);
    }
}
```

Then register your custom server instead of the base `RestifyServer`:

```php
use App\Mcp\ApplicationServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('restify', ApplicationServer::class)->name('mcp.restify');
```

### Generating Tokens with Tool Selection

To create a token creation UI, you need to show users which tools are available and let them select which ones to grant access to:

```php
use Binaryk\LaravelRestify\MCP\RestifyServer;

// Get all available tools
$server = app(RestifyServer::class);
$allTools = $server->getAllAvailableTools();

// Returns a collection with all tools, regardless of mode:
// [
//     ['name' => 'users-index', 'title' => 'List Users', 'description' => '...', 'category' => 'CRUD Operations'],
//     ['name' => 'users-store', 'title' => 'Create User', 'description' => '...', 'category' => 'CRUD Operations'],
//     ['name' => 'posts-publish-action', 'title' => 'Publish Post', 'description' => '...', 'category' => 'Actions'],
// ]

// Group tools by category for better UI
$groupedTools = $allTools->toSelectOptions();

// Returns:
// [
//     ['category' => 'CRUD Operations', 'tools' => [...]],
//     ['category' => 'Actions', 'tools' => [...]],
//     ['category' => 'Getters', 'tools' => [...]],
// ]
```

### Example Token Creation Flow

Here's a complete example of creating a token with specific tool permissions:

```php
use App\Models\McpToken;
use Illuminate\Support\Str;

// 1. Show available tools to user
$server = app(RestifyServer::class);
$availableTools = $server->getAllAvailableTools()->toSelectOptions();

// 2. User selects which tools to grant access to
$selectedTools = [
    'users-index',
    'users-show',
    'posts-index',
    'posts-store',
    'posts-publish-action',
];

// 3. Generate the token
$plainTextToken = Str::random(64);

// 4. Store token with permissions
$mcpToken = McpToken::create([
    'name' => 'AI Agent Token',
    'token' => hash('sha256', $plainTextToken),
    'allowed_tools' => $selectedTools, // Cast to JSON in model
    'user_id' => auth()->id(),
    'expires_at' => now()->addDays(30),
]);

// 5. Return plain text token to user (only shown once)
return response()->json([
    'token' => $plainTextToken,
    'allowed_tools' => $selectedTools,
]);
```

### Database Schema Example

Here's a suggested database schema for storing MCP tokens with permissions:

```php
Schema::create('mcp_tokens', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Token description
    $table->string('token')->unique(); // Hashed token
    $table->json('allowed_tools')->nullable(); // Array of tool names
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

And the corresponding Eloquent model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McpToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'allowed_tools',
        'user_id',
        'expires_at',
    ];

    protected $casts = [
        'allowed_tools' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Permission Behavior in Different Modes

**Direct Mode:**
- Tools without permission are filtered out of the tools list
- AI agent only sees tools they have access to
- Attempting to use a restricted tool results in "tool not found"

**Wrapper Mode:**
- The 4 wrapper tools are always visible (discover, get-operations, get-details, execute)
- When discovering repositories, only those with ≥1 accessible operation are shown
- When listing operations, only permitted operations appear
- Attempting to get details or execute a restricted operation throws `AuthorizationException`

### Getting Authorized Tools at Runtime

You can get only the tools the current user/token can access:

```php
$server = app(RestifyServer::class);
$authorizedTools = $server->getAuthorizedTools();

// Returns only tools that pass the canUseTool() check
// Useful for showing "Your API Access" in a dashboard
```

### Security Best Practices

1. **Always hash tokens** before storing in the database (use `hash('sha256', $token)`)
2. **Generate tokens with sufficient entropy** (at least 64 random characters)
3. **Implement token expiration** and enforce it in `canUseTool()`
4. **Log token usage** by updating `last_used_at` on each request
5. **Revoke tokens** by deleting the database record
6. **Use HTTPS** to protect tokens in transit
7. **Implement rate limiting** per token to prevent abuse
8. **Audit permission changes** when updating `allowed_tools`
