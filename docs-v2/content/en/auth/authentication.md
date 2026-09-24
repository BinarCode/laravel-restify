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

Under the hood, the second step is its own command, `php artisan restify:auth-macro`, which you can also run on its own if you only need the `routes/api.php` line appended.

## Prerequisites

Migrate the `users`, `password_reset_tokens` table (they already exist into a fresh Laravel app).

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

Note: Email and password fields are required. The `password` must be confirmed (a matching `password_confirmation`) and at least 6 characters. The `email` uniqueness check runs against the table configured in `restify.auth.table` (`config/restify.php`), which defaults to `users`.

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

You can override this template per request by sending a `url` field alongside the email:

```json
{
    "email": "demo@restify.com",
    "url": "https://app.example.com/password/reset?token={token}&email={email}"
}
```

For security, `url` is only accepted when its scheme, host, and port all match one of: the configured `password_reset_url`, or `config('restify.auth.frontend_app_url')`. `frontend_app_url` defaults to `env('APP_URL')`, so it still covers your app URL when `FRONTEND_APP_URL` is unset; `config('app.url')` itself is never checked, since once `FRONTEND_APP_URL` is set to a different host, `app.url` is your API host and a reset link should never target it. A subdomain of an allowed host does not count as a match, an `http` url is rejected when the matching config entry is `https`, a different port is rejected even on an otherwise matching host, and a url carrying userinfo (`user:pass@host`) is always rejected outright. Anything that doesn't match is rejected with a `422` validation error. This stops the endpoint from being used to mail a victim a valid reset link pointing at an attacker-controlled domain.

The path, query string, and fragment are also checked, but only for their characters, not their destination: they may only contain RFC 3986 unreserved/reserved characters, narrowed to exclude `[`, `]`, `(`, `)`, `<`, `>`, `"`, `'`, `` ` ``, and `{`/`}` (outside the literal `{token}`/`{email}` placeholders), plus whitespace and control characters. This is because the mailed reset link is rendered through a Markdown template, and those characters could otherwise be used to break out of the intended `[text](url)` link and inject a second, attacker-controlled one carrying the real token - so whatever frontend host you allow here must still not itself have an open redirect, or an attacker could send a victim through it to an arbitrary destination.

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

For example, if you previously published the register route, your `routes/api.php` file might look like this:

```php
// ...

Route::restifyAuth(actions: ['login', 'logout', 'verifyEmail', 'forgotPassword', 'resetPassword']);

// ...
```

You can run the `restify:auth` command again with a different `--actions` value at any time - there's no need to edit `routes/api.php` by hand first. The command merges the newly published actions into the existing list for you, and re-running it for an action that's already published is a safe no-op.
