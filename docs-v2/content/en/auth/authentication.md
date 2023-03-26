---
title: Authentication setup
menuTitle: Authentication
category: Auth
position: 1
---

Laravel Restify has the support for a facile authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum#api-token-authentication).

Now you can finally enjoy the auth setup (`register`, `login`, `forgot`, and `reset password`).

## Prerequisites

Migrate the `users`, `password_resets` table (they already exist into a fresh Laravel app).

<alert type="success">

Laravel 10 automatically ships with Sanctum, so you don't have to install it.

</alert>

### Install sanctum

See the docs [here](https://laravel.com/docs/sanctum#installation). You don't need to add `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,` in your `'api'` middleware group. 

You only have to run these 3 commands: 

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

Make sure you have the `\Laravel\Sanctum\HasApiTokens` trait to your `User` model. 
Laravel 10 will automatically add this trait to your `User` model.

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

Restify provides you a simple way to add all of your auth routes prepared. Simply add in your `routes/api.php`:

```php
Route::restifyAuth();
```

And voila, now you have auth routes ready to be used.

These are the default routes provided by restify: 

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

All of these routes are handled by default, so you can just use them facilely. However, you can customize each of them by exporting auth controllers: 

```shell
php artisan restify:auth
```
Now you have all the auth controllers and blade email files exported into your project.

## Sanctum Middleware

Next, add the `auth:sanctum` middleware after the `api` middleware in your config file to protect all the restify's routes:

```php
/config/restify.php
    'middleware' => [
        'api',
        'auth:sanctum',
        ...
    ],
```

## Login

Let's ensure the authentication is working correctly. Create a user in the DatabaseSeeder class:

```php
// DatabaseSeeder.php
\App\Models\User::factory()->create([
   'name' => 'Test User',
   'email' => 'test@example.com',
   'password' => \Illuminate\Support\Facades\Hash::make('password'),
]);
```

Seed it: 

```shell
php artisan db:seed
```

Now you can test the login with Curl or Postman:

```shell
curl -X POST "http://restify-app.test/api/login" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{
             "email": "test@example.com",
             "password": "password"
         }'
```

So you should see the response like this: 

```json
{
    "id": "11",
    "type": "users",
    "attributes": {
        "name": "Test User",
        "email": "test@example.com"
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": false,
        "authorizedToUpdate": false,
        "authorizedToDelete": false,
        "token": "1|f7D1qkALtM9GKDkjREKpwMRKTZg2ZnFqDZTSe53k"
    }
}
```

## Register

Let's see how to register a new user in the application. You can test the registration using Curl or Postman.

Use the following endpoint for registration:

`http://restify-app.test/api/register`

And send this payload:

```json
{
    "name": "John Doe",
    "email": "demo@restify.com",
    "password": "secret!",
    "password_confirmation": "secret!"
}
```

Note: Email and password fields are required.

Now, you can send a POST request with Curl:

```shell
curl -X POST "http://restify-app.test/api/register" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{
             "name": "John Doe",
             "email": "demo@restify.com",
             "password": "secret!",
             "password_confirmation": "secret!"
         }'
```

You should see the response like this:

```json
{
    "id": "12",
    "type": "users",
    "attributes": {
        "name": "John Doe",
        "email": "demo@restify.com"
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": false,
        "authorizedToUpdate": false,
        "authorizedToDelete": false,
        "token": "2|z8D2rkBLtN8GKDkjREKpwMRKTZg2ZnFqDZTSe53k"
    }
}
```

## Forgot Password

To initiate the password reset process, use the following endpoint:

`{{host}}/api/forgotPassword`

And send this payload:

```json
{
    "email": "demo@restify.com"
}
```

After making a POST request to this endpoint, an email will be sent to the provided email address containing a link to reset the password. The link looks like this:

`'password_reset_url' => env('FRONTEND_APP_URL').'/password/reset?token={token}&email={email}',`

This configuration can be found in the `config/restify.php` file. The FRONTEND_APP_URL should be set to the URL of your frontend app, where the user lands when they click the action button in the email. The "token" is a variable that will be used to reset the password later on.

To view the email content during development, you can change the following configuration in your .env file:

```dotenv
MAIL_MAILER=log
```

This will log the email content to the `laravel.log` file, allowing you to see the password reset email without actually sending it.

Now, you can send a POST request with Curl:

```shell
curl -X POST "http://restify-app.test/api/forgotPassword" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{
            "email": "demo@restify.com"
         }'
```

If the email is successfully sent, you'll receive a response similar to the following:

```json
{
    "message": "Reset password link sent to your email."
}
```

Now, the user can follow the link in the email to reset their password.


## Reset Password

After the user has received the password reset email from the Forgot Password process, they can reset their password using the following endpoint:

`http://restify-app.test/api/resetPassword`

The payload should include the token and email received from the password reset email:

```json
{
    "token": "7e474bb9118e736306de27126343644a7cb0ecdaec558fdef30946d15225bc07",
    "email": "demo@restify.com",
    "password": "new_password",
    "password_confirmation": "new_password"
}
```
Now, you can send a POST request with Curl:

```shell
curl -X POST "http://restify-app.test/api/resetPassword" \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -d '{
             "token": "0d20b6cfa48f2bbbb83bf913d5e329207149f74d7b22d59a383d321c7af7fd5e",
             "email": "demo@restify.com",
             "password": "new_password",
             "password_confirmation": "new_password"
         }'
```

If the password reset is successful, you should receive a response similar to the following:

```json
{
    "message": "Your password has been successfully reset."
}
```

Now the user's password has been successfully reset, and they can log in with their new password.

