---
title: Authentication setup
menuTitle: Authentication
category: Auth
position: 1
---

Laravel Restify has the support for a facile authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum#api-token-authentication).

Now you can finally enjoy the auth setup (`register`, `login`, `forgot`, and `reset password`).

## Quick start

tl;dr: 

If you run on Laravel 10 or higher, you can use this command that will do all the setup for you:

```shell script
php artisan restify:setup-auth
```

This command will:

- **ensures** that `Sanctum` is installed and configured as the authentication provider in the `config/restify.php` file
- **appends** the `Route::restifyAuth();` line to the `routes/api.php` file to add the authentication routes

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

And voilà, now you have auth routes ready to be used.

These are the default routes provided by restify: 

| Verb           | URI                                      | Action         | 
| :------------- |:-----------------------------------------|:---------------|
| **POST**           | `/api/register`                          | register       |
| **POST**           | `/api/login`                             | login          |
| **POST**           | `/api/restify/forgotPassword`            | forgotPassword |
| **POST**           | `/api/restify/resetPassword`             | resetPassword  |
| **POST**           | `/api/restify/verify/{id}/{emailHash}`   | verifyEmail    |

<alert type="info">

The `register` and `login` routes are outside the base `restify` prefix because they don't have to follow the `auth` middleware defined in the `config/restify.php` config file.

</alert>


You can also pass an `actions` argument, which is an array of actions you want to register. For example:

```php
Route::restifyAuth(actions: ['login', 'register']);
```

By using the `actions` argument, only the specified routes will be registered. If no `actions` argument is passed, Restify will register all the routes by default.


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

Let's ensure the authentication is working correctly. Create a user in the `DatabaseSeeder` class:

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

### Authorization

We will discuss the authorization in more details here [Authorization](/auth/authorization). But for now let's see a simple example. 

After a successful login, you will receive an authentication token. You should include this token as a `Bearer` token in the Authorization header for your subsequent API requests using [Postman](https://learning.postman.com/docs/sending-requests/authorization/#bearer-token), axios library, or cURL.

Here's an axios example for retrieving the user's profile with the generated token:

```js
import axios from 'axios';

const token = '1|f7D1qkALtM9GKDkjREKpwMRKTZg2ZnFqDZTSe53k';

axios.get('http://restify-app.test/api/restify/profile', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
})
.then(response => {
    console.log(response.data);
})
.catch(error => {
    console.error(error);
});
```

Here's a cURL example for retrieving the user's profile with the generated token:
```bash
curl -X GET "http://restify-app.test/api/restify/profile" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer 1|f7D1qkALtM9GKDkjREKpwMRKTZg2ZnFqDZTSe53k"
```

Replace `http://restify-app.test` with your actual domain and use the authentication token you received after logging in.

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


## Customizing Authentication Controllers

You can publish the authentication controllers from the Restify package to your own application, allowing you to customize their behavior as needed. To publish the controllers, run the following command:

```shell
php artisan restify:auth
```

This command will copy the authentication controllers to the `app/Http/Controllers/Restify` directory in your Laravel project.

The command accepts an optional `--actions` parameter, which allows you to specify which controllers you want to publish. If no action is passed, the command will publish all controllers and the `ForgotPasswordNotification`. For example, to publish only the `login` and `register` controllers, run:

```shell
php artisan restify:auth --actions=login,register
```

Now, you can make any necessary changes to these controllers to fit your specific requirements.

### Customizing the Register Route

In a real-world scenario, you might need to customize only the register route. To do this, you can use the `restify:auth` command with the `--actions` option to publish only the register controller:

  ```shell
php artisan restify:auth --actions=register
```

After running the command, the register controller will be published to your application, and you can modify it to fit your requirements.

<alert type="warning">

Important Note: If you want to publish other actions in the future, you'll need to manually update the `routes/api.php` file before running the restify:auth command again. Remove any previously published Restify routes, and keep the `Route::restifyAuth();` line so that the new routes can be correctly published.

</alert>

For example, if you previously published the register route, your `routes/api.php` file might look like this:

```php
// ...

Route::restifyAuth(actions: ["login", "resetPassword", "forgotPassword", "verifyEmail"]);

// ...
```

Before running the `restify:auth` command again, revert the file to its original state:

```php
// ...

Route::restifyAuth();

// ...
```

Now you can run the `restify:auth` command with other actions, and the routes will be published correctly.
