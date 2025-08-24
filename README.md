<p align="center"><img src="/docs-v2/static/logo.png"></p>

<p align="center">
    <a href="https://github.com/BinarCode/laravel-restify/actions"><img src="https://github.com/BinarCode/laravel-restify/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/d/total.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/v/stable.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/binaryk/laravel-restify"><img src="https://poser.pugx.org/binaryk/laravel-restify/license.svg" alt="License"></a>
</p>

The first fully customizable Laravel [JSON:API](https://jsonapi.org) builder with MCP and GraphQL support. "CRUD" and protect your resources with 0 (zero) extra line of code.

<div>
<a href="https://restifytemplates.com">
<img alt="Save weeks of API development" src="/docs-v2/static/starter-kit.png">
</a>
</div>

## ⚡ Laravel Restify Templates - Save Weeks of Development

Looking to 10x your API development speed? Check out our production-ready API templates at [RestifyTemplates.com](https://restifytemplates.com). Get complete authentication, roles & permissions, team management, and more - all built on Laravel Restify. Zero configuration needed. Deploy in minutes instead of weeks.

## Installation

You can install the package via composer:

```bash
composer require binaryk/laravel-restify
```

## Playground

You can find a playground in the [Restify Demo GitHub repository](https://github.com/BinarCode/restify-demo).

## Videos

If you are a visual learner, checkout [our video course](https://www.binarcode.com/learn/restify) for the Laravel Restify.

## Quick start

Setup package:

```bash
php artisan restify:setup
```

Generate repository:

```bash
php artisan restify:repository Dream --all
```

Now you have the REST CRUD over dreams and this beautiful repository:

<p align="center"><img src="/docs-v2/static/tile.png"></p>

Now you can go into Postman and check it out: 

```bash
GET: http://laravel.test/api/restify/dreams
```

```bash
POST: http://laravel.test/api/restify/dreams
```

```bash
GET: http://laravel.test/api/restify/dreams/1
```

```bash
PUT: http://laravel.test/api/restify/dreams/1
```

```bash
DELETE: http://laravel.test/api/restify/dreams/1
```

## 🤖 AI-Powered Development with MCP

Laravel Restify now includes **Model Context Protocol (MCP)** integration, enabling seamless AI agent interactions with your API resources. This powerful feature allows AI agents to understand, query, and manipulate your data through structured tool interfaces.

> **🔥 New!** MCP support enables AI agents to work directly with your Laravel Restify APIs, providing intelligent data access, automated operations, and enhanced development workflows.

**Key MCP Features:**
- **AI Agent Integration**: Connect Claude, GPT, and other AI agents directly to your APIs
- **Structured Tool Interfaces**: Automatically generated tools for CRUD operations  
- **Security & Authorization**: Full Laravel authorization integration maintained
- **Developer MCP Server**: Dedicated server for enhanced Laravel Restify development

## MCP Server for Developers

Laravel Restify provides an MCP (Model Context Protocol) server designed for developers working with Laravel Restify APIs. This server enables AI agents to access documentation, create repositories, actions, and getters through structured tools.

**Repository**: [https://github.com/BinarCode/laravel-restify-mcp](https://github.com/BinarCode/laravel-restify-mcp)

### Features

- **Documentation Access**: Query Laravel Restify documentation directly
- **Repository Generation**: Create new repositories with proper structure
- **Action Creation**: Generate custom actions for your API resources  
- **Getter Development**: Build custom getters for data retrieval
- **Code Examples**: Get contextual code examples and best practices

### Installation

```bash
npm install -g @binarcode/laravel-restify-mcp
```

### Usage with AI Agents

Configure your AI agent (Claude Desktop, etc.) to use the MCP server for enhanced Laravel Restify development assistance.

## Usage

See the [official documentation](https://restify.binarcode.com).

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email eduard.lupacescu@binarcode.com or [message me on twitter](https://twitter.com/LupacescuEuard) instead of using the issue tracker.

## Credits

- [Eduard Lupacescu](https://twitter.com/LupacescuEuard)
- [Koen Koenster](https://github.com/Koenster)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

