# Installation

[[toc]]

## Requirements

Laravel Restify has a few requirements you should be aware of before installing:

- Composer
- Laravel Framework >= 7.0

## Installing Laravel Restify

```bash
composer require binaryk/laravel-restify
```

## Setup Laravel Restify
After the installation, the package requires a setup process: 

```shell script
php artisan restify:setup
```

The command above does: 

- [ ] publish the `config/restify.php` configuration file
- [ ] create the `providers/RestifyServiceProvider` and will add it in your `config/app.php` 
- [ ] create a new `app/Restify` directory
- [ ] create an abstract `app/Restif/Repository.php`
- [ ] scaffolding a `app/Restify/UserRepository` repository for users CRUD

:::tip Package Stability

If you are not able to install Restify into your application because of your `minimum-stability` setting,
 consider setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. 
 This will allow you to install Laravel Restify while still preferring stable package 
 releases for your application.
:::

## Quick start

Having the package setup and users table migrated, you should be good to perform the first API request:

```http request
GET: /api/restify/users?perPage=10
```

This should return the users list paginated and formatted according to [JSON:API](https://jsonapi.org/format/) standard.

## Configurations

### Prefix

As you notice the default prefix for the restify api is `/api/restify`. This can be changed from the `app/restify.php` file:

```php
'base' => '/api/restify',
```

### Middleware

One important configuration is the restify default middlewares: 

```php
'middleware' => [
    'api',
    Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent::class,
    Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify::class,
]
```

### Sanctum authorization

Usually you want to authorize your api (allow access only to authenticated users). For this purpose you can simply add another middleware. For the `sanctum`, Restify provides `Binaryk\LaravelRestify\Http\Middleware\RestifySanctumAuthenticate` middleware.

### Exception Handling

The `exception_handler` configuration allow you to use another default Exception Handler instead of the one Laravel provides by default. If you want to keep the default one, just left this configuration as `null`.
