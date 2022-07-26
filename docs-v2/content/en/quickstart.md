---
title: Installation
category: Getting Started
---

## Requirements



Laravel Restify has a few requirements that you should be mindful of before installing:

<list :items="[
  'PHP >= 8.0',
  'Laravel Framework >= 8.0'
]">
</list>

## Installation

```bash
composer require binaryk/laravel-restify
```

## Setup

After the installation, the package requires a setup process:

```shell script
php artisan restify:setup
```

The command above:

- **publishes** the `config/restify.php` configuration file
- **creates** the `providers/RestifyServiceProvider` and will add it in the `config/app.php`
- **creates** a new `app/Restify` directory
- **creates** an abstract `app/Restif/Repository.php`
- **scaffolds** a `app/Restify/UserRepository` repository for users CRUD

### Package Stability

<alert>

If you are not able to install Restify into your application because of your `minimum-stability` setting, consider
setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. This will allow you to
install Laravel Restify while still preferring stable package releases for your application.

</alert>

## Quick start

Having the package setup and users table migrated, you should be good to perform the first API request:

```http request
GET: /api/restify/users?perPage=10&page=2
```

or use the [json api](https://jsonapi.org/profiles/ethanresnick/cursor-pagination/#auto-id-pagesize) format:

```http request
GET: /api/restify/users?page[size]=10&page[number]=2
```

This should return the users list paginated and formatted according to [JSON:API](https://jsonapi.org/format/) standard.

## Configurations

### Prefix

As you can see, the default prefix for the restify api is `/api/restify`. This can be changed from the `app/restify.php`
file:

```php
'base' => '/api/restify',
```

### Middleware

One important configuration is the restify's default middleware:

```php
// config/restify.php

'middleware' => [
    'api',
    // 'auth:sanctum',
    Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent::class,
    Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify::class,
]
```

### Sanctum authentication

Normally, you would want to authenticate your api (allow access only to authenticated users). For this purpose, you can simply add another middleware. For the `sanctum`, you can add the `auth:sanctum`. Make sure you put this right after `api` middleware.

Restify also provides the `EnsureJsonApiHeaderMiddleware` middleware, which enforces you to use the `application/application-json` `Accept header` for your API requests. If you prefer to add this middleware, when using the Postman/Insomnia API client, make sure that this `Accept header` is applied.

## Generate repository

Creating a new repository can be done via restify command:

```shell script
php artisan restify:repository PostRepository
```

If you want to generate the `Policy`, `Model`, and `migration` as well, then you can use the `--all` option:

```shell script
php artisan restify:repository PostRepository --all
```

## Generate policy

Since the authorization is based on using the Laravel Policies, a good way of generating a complete policy for an entity is by
using the restify command:

```shell script
php artisan restify:policy PostPolicy
```
