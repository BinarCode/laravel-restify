---
title: Authentication setup
menuTitle: Authentication
category: Auth
position: 1
---

Laravel Restify has support for authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum#api-token-authentication).

You'll finally enjoy the auth setup (`register`, `login`, `forgot` and `reset password`).

## Prerequisites
- Migrate the `users`, `password_resets` table (they already exists into a fresh Laravel app).

- Migrate the `personal_access_tokens` table, provided by sanctum.

- Install laravel sanctum. See the docs [here](https://laravel.com/docs/sanctum#installation). You don't need to add `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,` in your `'api'` middleware group. So you only need to run these 3 commands: 

```shell script
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

- Define your authenticatable class in the config file: 

```php
// config/restify.php

'auth' => [
    ...
   'user_model' => \App\Models\User::class,
]
```

- Make sure your authenticatable class (usually `App\Models\User`) implements: `Illuminate\Contracts\Auth\Authenticatable` (or simply extends the `Illuminate\Foundation\Auth\User` class as it does into a fresh laravel app.)

- Make sure the `App\Models\User` model implements the `Binaryk\LaravelRestify\Contracts\Sanctumable` contract.

- Add `\Laravel\Sanctum\HasApiTokens` trait to your `User` model.

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

All of these routes are handle by default, so you can just use them. However, you can customize each of them by exporting auth controllers: 

```shell
php artisan restify:auth
```
So you have all auth controllers, blade email files exported into your project.

Next, add the `auth:sanctum` middleware after the `api` middleware in your config file to protect all restify routes:

```php
/config/restify.php
    'middleware' => [
        'api',
        'auth:sanctum',
        ...
    ],
```
