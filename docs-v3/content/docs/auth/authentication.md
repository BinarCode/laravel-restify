---
title: Authentication setup
menuTitle: Authentication
category: Auth
position: 1
---

Laravel Restify provides comprehensive authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum#api-token-authentication), including register, login, logout, forgot password, reset password, and email verification.

## Quick start

**This is everything you need to have authentication in place:**

If you run on Laravel 11 or higher, use this single command for complete authentication setup:

```shell script
php artisan restify:setup-auth
```

This command will:

- **Ensures** that `Sanctum` is installed and configured as the authentication provider in the `config/restify.php` file
- **Identifies** the User model and updates the `auth.user_model` configuration automatically
- **Appends** the `Route::restifyAuth();` line to the `routes/api.php` file to add the authentication routes

**That's it!** Your authentication system is ready to use with register, login, logout, password reset, and email verification.

---

**Prefer manual setup?** You can configure everything step-by-step as described below, starting with the [Install sanctum](#install-sanctum) section.

## Install sanctum

<alert type="success">

Laravel 11 automatically ships with Sanctum, so you don't have to install it.

</alert>

See the documentation [here](https://laravel.com/docs/sanctum#installation). You don't need to add `\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,` to your `'api'` middleware group. 

You only have to run these 3 commands: 

```shell script
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Define auth model

Define your authenticatable class in the config file: 

```php [config/restify.php]
'auth' => [
    ...
   'user_model' => \App\Models\User::class,
]
```

The `User` model should extend the `Illuminate\Foundation\Auth\User` class or implement the `Illuminate\Contracts\Auth\Authenticatable` interface. 

<alert type="info">

Make sure you have the `\Laravel\Sanctum\HasApiTokens` trait to your `User` model. 
Laravel 11 will automatically add this trait to your `User` model.

</alert>


```php [User.php]
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

And that's it! You now have authentication routes ready to use.

These are the default routes provided by restify: 

| Verb           | URI                                      | Action         | 
| :------------- |:-----------------------------------------|:---------------|
| **POST**           | `/api/register`                          | register       |
| **POST**           | `/api/login`                             | login          |
| **POST**           | `/api/logout`                            | logout         |
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

When using the `actions` argument, only the specified routes will be registered. If no `actions` argument is provided, Restify will register all routes by default.


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

```php [DatabaseSeeder.php]
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

You should see a response like this: 

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

We will discuss authorization in more detail in the [Authorization](/auth/authorization) section. For now, let's see a simple example. 

After a successful login, you will receive an authentication token. You should include this token as a `Bearer` token in the Authorization header for your subsequent API requests using [Postman](https://learning.postman.com/docs/sending-requests/authorization/#bearer-token), axios library, or cURL.

Here's an Axios example for retrieving the user's profile with the generated token:

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

## Token Management

Laravel Restify uses Sanctum tokens for API authentication with the following characteristics:

### Token Expiration

By default, tokens **never expire**. You can configure token expiration in your `config/restify.php` file:

```php [config/restify.php]
'auth' => [
    'token_ttl' => null, // Default: tokens never expire
    // Set to minutes for token expiration
    // 'token_ttl' => 60, // Tokens expire after 60 minutes (1 hour)
]
```

### Logout

To revoke an authentication token before it expires, use the logout endpoint:

```bash
curl -X POST "http://restify-app.test/api/logout" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer 1|f7D1qkALtM9GKDkjREKpwMRKTZg2ZnFqDZTSe53k"
```

Successful logout returns:

```json
{
    "message": "Successfully logged out"
}
```

### Multiple Devices

Laravel Sanctum automatically handles multiple device authentication:

- Each login creates a **new token**
- Users can be logged in on multiple devices simultaneously
- Each device has its own token
- Logout only revokes the specific token used

## Register

Let's see how to register a new user in the application. You can test the registration using cURL or Postman.

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

Now, you can send a POST request with cURL:

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

## Email Verification

Email verification is only available when your User model implements the `MustVerifyEmail` interface.

### Enable Email Verification

Update your User model to implement email verification:

```php [app/Models/User.php]
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    
    // ... rest of your User model
}
```

When a user registers, their `email_verified_at` field is set to `NULL`, and Restify automatically sends a verification email using the `VerifyEmail` notification.

### How It Works

When the `MustVerifyEmail` interface is implemented, during registration Restify will:

1. **Send verification email** using `Binaryk\LaravelRestify\Notifications\VerifyEmail`
2. **Generate signed verification URL** pointing directly to the Restify API endpoint
3. **Include parameters** in the signed URL:
   - `id` → User's ID
   - `hash` → SHA1 hash of the user's email address

The email contains a CTA (Call-To-Action) button that links directly to the API verification endpoint.

### Verification Flow

1. **Registration**: User registers → `email_verified_at` is `NULL`
2. **Email Sent**: User receives verification email with CTA link
3. **Direct API Call**: Email CTA redirects directly to `/api/restify/verify/{id}/{hash}` 
4. **Email Verification**: API validates the signed URL and sets `email_verified_at` to current timestamp
5. **Frontend Redirect**: 
   - **Success**: If validation succeeds, API redirects user to frontend URL from `config('restify.auth.user_verify_url')`
   - **Failure**: If hash is invalid, API redirects to frontend with `?success=false&message=Invalid hash`

### Frontend Integration

With the updated flow, users click the CTA in the email and are redirected directly to the API for verification. After verification (successful or failed), they land on your frontend application.

Your frontend should handle the verification result by checking URL parameters:

**Success case**: User lands on your configured URL (no additional parameters)
```
https://your-frontend-app.com/email/verify?id=1&hash=abc123
```

**Failure case**: User lands on your configured URL with error parameters
```
https://your-frontend-app.com/email/verify?id=1&hash=abc123&success=false&message=Invalid hash
```

**Frontend JavaScript example**:
```javascript
// Check for verification result
const urlParams = new URLSearchParams(window.location.search);
const success = urlParams.get('success');
const message = urlParams.get('message');

if (success === 'false') {
    // Handle verification failure
    console.error('Verification failed:', message);
    // Show error message to user
    showErrorMessage(message || 'Email verification failed');
} else {
    // Handle verification success (success param is absent for successful verifications)
    console.log('Email verified successfully');
    // Show success message and redirect or update UI
    showSuccessMessage('Your email has been verified successfully!');
}
```

### cURL Example

For testing purposes, you can directly call the verification endpoint:

```bash
curl -X GET "http://restify-app.test/api/restify/verify/1/abc123hash?signature=xyz&expires=123456" \
     -H "Accept: application/json"
```

**Note**: The actual verification URL includes a signed signature and expires parameter for security.

### Success Response

On successful verification, the API redirects to your configured frontend URL. For direct API calls (like cURL), you might receive a success response or redirect.

### Configuration

Configure your frontend URL in `config/restify.php`:

```php
'auth' => [
    'user_verify_url' => env('FRONTEND_APP_URL').'/email/verify?id={id}&hash={emailHash}',
]
```

## AI Agent Authentication

Laravel Restify's MCP server uses the same Sanctum authentication system. AI agents authenticate using the same tokens that humans use.

### Token Generation for AI Agents

Users can generate API tokens for AI agents through your application interface, or programmatically:

```php
// Generate a token for an AI agent
$user = auth()->user();
$token = $user->createToken('AI Agent Token')->plainTextToken;

// Token can be used by AI agents for MCP server access
```

### MCP Server Authentication

When configuring the MCP server, tokens are passed in the Authorization header:

```php [config/ai.php]
Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum'])  // Same authentication as REST API
    ->name('mcp.restify');
```

### AI Agent Usage

AI agents use the same Bearer token authentication:

```bash
# AI agent making MCP request
curl -X GET "http://restify-app.test/mcp/restify" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer 1|f7D1qkALtM9GKDkjREKpwMRKTZg2ZnFqDZTSe53k"
```

### Benefits

- **Unified Authentication**: Same auth system for humans and AI agents
- **Consistent Permissions**: Same authorization policies apply
- **Token Management**: Same logout/expiration rules
- **Security**: Same security model across all interfaces

<alert>

**Learn More**: Check the [MCP Server Guide](/mcp/mcp) for detailed AI agent configuration.

</alert>

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

Now, you can send a POST request with cURL:

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
Now, you can send a POST request with cURL:

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

## Authentication Configuration

Laravel Restify provides several configuration options in `config/restify.php` under the `auth` key:

### Available Options

```php [config/restify.php]
'auth' => [
    // User model for authentication
    'user_model' => \App\Models\User::class,
    
    // Token expiration time in minutes (default: null - never expire)
    'token_ttl' => null,
    
    // Password reset URL (for frontend)
    'password_reset_url' => env('FRONTEND_APP_URL').'/password/reset?token={token}&email={email}',
    
    // Email verification URL (for frontend)
    'user_verify_url' => env('FRONTEND_APP_URL').'/email/verify?id={id}&hash={emailHash}',
],
```

### Environment Variables

Add these to your `.env` file:

```bash
# Frontend application URL for auth redirects
FRONTEND_APP_URL=https://your-frontend-app.com

# Mail configuration for password reset and verification emails
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### Token Configuration

- **Default TTL**: `null` (never expire)
- **Custom TTL**: Set any value in minutes
- **Never Expire**: Set `token_ttl` to `null`

```php
// Examples
'token_ttl' => null,  // Never expire (default)
'token_ttl' => 30,    // 30 minutes
'token_ttl' => 60,    // 1 hour
'token_ttl' => 1440,  // 24 hours
```

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

**Important Note:** If you want to publish other actions in the future, you'll need to manually update the `routes/api.php` file before running the `restify:auth` command again. Remove any previously published Restify routes, and keep the `Route::restifyAuth();` line so that the new routes can be correctly published.

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
