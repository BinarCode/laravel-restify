---
title: Model Context Protocol (MCP)
menuTitle: Restify Boost
category: Boost
position: 20
---

## MCP Server for Laravel Restify Developers

Restify Boost provides a dedicated **MCP server for developers** that enhances the development experience when working with Laravel Restify APIs.

**Repository**: [https://github.com/BinarCode/laravel-restify-boost](https://github.com/BinarCode/laravel-restify-boost)

### Developer MCP Server Features

The Laravel Restify MCP server provides AI agents with powerful development tools:

- **📚 Documentation Access**: Query Laravel Restify documentation directly from your AI agent
- **🏗️ Repository Generation**: Create new repositories with proper structure and conventions
- **⚡ Action Creation**: Generate custom actions for your API resources with validation and best practices
- **🔍 Getter Development**: Build custom getters for specialized data retrieval operations
- **💡 Code Examples**: Get contextual code examples and implementation guidance
- **🎯 Best Practices**: Receive Laravel Restify best practices and architectural guidance

### Installation & Setup

#### Install the MCP Server

```bash
composer require --dev binarcode/laravel-restify-boost
```

#### Configure AI Agents

Configure your AI agent (Claude Desktop, Cursor, etc.) to use the MCP server:

```json
{
  "mcpServers": {
    "laravel-restify": {
      "command": "php",
      "args": [
        "artisan",
        "restify-boost:start"
      ]
    }
  }
}
```

#### Usage Examples

Once configured, your AI agent can help with:

**Creating Repositories:**
```
AI: Create a PostRepository with title, content, and author fields
```

**Generating Actions:**
```
AI: Create a PublishPostAction that validates publish dates and notifies subscribers
```

**Building Getters:**
```
AI: Generate a PostAnalyticsGetter that returns engagement metrics for date ranges
```

**Documentation Queries:**
```
AI: How do I implement field validation in Laravel Restify?
AI: Show me examples of custom repository authorization
```
