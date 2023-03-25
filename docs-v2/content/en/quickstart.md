---
title: Quickstart
category: Getting Started
---

## Requirements


Laravel Restify has a few requirements that you should be mindful of before installing:

<list :items="[
  'PHP ^8.0',
  'Laravel Framework ^8.0 for Restify <= 6.x',
  'Laravel Framework ^9.0 for Restify ^7.x',
]">
</list>

<list :items="[
  'PHP >= 8.1',
  'Laravel Framework ^10.0 for Restify ^8.x'
]">
</list>

## Installation

```bash
composer require binaryk/laravel-restify
```

### Package Stability

<alert>

If you are not able to install Restify into your application because of your `minimum-stability` setting, consider
setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. This will allow you to
install Laravel Restify while still preferring stable package releases for your application.

</alert>

## Setup

After the installation, the package requires a setup process:

```shell script
php artisan restify:setup
```

The command above:

- **publishes** the `config/restify.php` configuration file and `action_logs` table migration
- **creates** the `providers/RestifyServiceProvider` and will add it in the `config/app.php`
- **creates** a new `app/Restify` directory
- **creates** an abstract `app/Restify/Repository.php`
- **scaffolds** a `app/Restify/UserRepository` repository for users CRUD

### Migrations

After the setup, you should run the migrations:

```shell script
php artisan migrate
```

## Generating Mock Data

To generate mock data for your database, you need to install the `doctrine/dbal` package as a development dependency:

```bash
composer require doctrine/dbal --dev
```
After installing the package, you can use the restify:stub command to generate mock data for a specific table:

```bash
php artisan restify:stub table_name --count=10
```

Replace table_name with the name of the table you want to generate mock data for and use the --count option to specify the number of records you want to create.

For example, to generate 10 users:

```shell
php artisan restify:stub users --count=10
```

## Quick start

Having the package setup and users table migrated and seeded, you should be good to perform the first API request:

```http request
GET: /api/restify/users?perPage=10&page=1
```

or use the [json api](https://jsonapi.org/profiles/ethanresnick/cursor-pagination/#auto-id-pagesize) format:

```http request
GET: /api/restify/users?page[size]=10&page[number]=1
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

#### Sanctum authentication

Normally, you would want to authenticate your api (allow access only to authenticated users). For this purpose, you can simply add another middleware. For the `sanctum`, you can add the `auth:sanctum`. Make sure you put this right after `api` middleware.

We will cover this more in the [Authentication](/auth/authentication) section.

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
