---
title: Authentication setup
menuTitle: Authentication
category: Auth
position: 1
---

Laravel Restify has support for authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum#api-token-authentication).

You'll finally enjoy the auth setup (`register`, `login`, `forgot` and `reset password`).

## Prerequisites

Migrate the `users`, `password_resets` table (they already exists into a fresh Laravel app).

### Install sanctum

See the docs [here](https://laravel.com/docs/sanctum#installation). You don't need to add `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,` in your `'api'` middleware group. 

So you only have to run these 3 commands: 

```shell script
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Define auth model

Define your authenticatable class in the config file: 

```php
// config/restify.php

'auth' => [
    ...
   'user_model' => \App\Models\User::class,
]
```

The `User` model should extend the `Illuminate\Foundation\Auth\User` class or implement the `Illuminate\Contracts\Auth\Authenticatable` interface. 

<alert type="info">

Ensure you didn't skip to add the `\Laravel\Sanctum\HasApiTokens` trait to your `User` model.

</alert>


```php
// User.php

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
```

## Define routes

Restify provides you a simple way to add all of your auth routes ready. Simply add in your `routes/api.php`:

```php
Route::restifyAuth();
```

And voila, now you have auth routes ready to be used.

These are default routes provided by restify: 

| Verb           | URI                                      | Action           | 
| :------------- |:-----------------------------------------| :----------------|
| **POST**           | `/api/register`                          | register         |
| **POST**           | `/api/login`                             | login            |
| **POST**           | `/api/restify/forgotPassword`            | forgot password  |
| **POST**           | `/api/restify/resetPassword`             | reset password   |
| **POST**           | `/api/restify/verify/{id}/{emailHash}`   | verify user      |

<alert type="info">

The `register` and `login` routes are outside the base `restify` prefix because they don't have to follow the `auth` middleware defined in the `config/restify.php` config file.

</alert>

## Export auth controllers

All of these routes are handle by default, so you can just use them. However, you can customize each of them by exporting auth controllers: 

```shell
php artisan restify:auth
```
So you have all auth controllers, blade email files exported into your project.

## Sanctum Middleware

Next, add the `auth:sanctum` middleware after the `api` middleware in your config file to protect all restify routes:

```php
/config/restify.php
    'middleware' => [
        'api',
        'auth:sanctum',
        ...
    ],
```
