---
title: Model Context Protocol (MCP)
menuTitle: MCP
category: MCP
position: 14
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
