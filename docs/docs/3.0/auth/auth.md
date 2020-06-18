# Authentication setup

Laravel Restify has support for authentication with Passport or Laravel Sanctum.

You'll finally enjoy the auth setup (`register`, `login`, `forgot` and `reset password`).

:::tip

Firstly make sure you have the setup the desired provider in the `restify.auth.provider`. Make sure you have installed and configured the [Laravel Sanctum](https://laravel.com/docs/7.x/sanctum#introduction) (or Passport) properly. 

The passport check could be done easily by using the follow Restify command: 

`php artisan restify:check-passport`

This command will become with suggestions if any setup is invalid.
:::

## Prerequisites
- When using the Restify authentication service, you will need to migrate the `users` and `password_resets` table (these 2 migrations are by default in a fresh laravel app, however you may modify the users table as you prefer)

- Make sure your authenticatable entity (usually `User`) implements: `Illuminate\Contracts\Auth\Authenticatable` (or `Illuminate\Contracts\Auth\Restifyable`)

## Register users

- Define a register route to an action controller:

```php
Route::post('register', 'AuthController@register');
```

- Inject the AuthService into your controller and call the register method:

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Binaryk\LaravelRestify\Services\AuthService;

class AuthController
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * This will validate the input, 
     * will register the user and return it back to the api
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $user = $this->authService->register($request->all());

        return Response::make(['data' => $user], 201);
    }
```

- Validating input

Restify will automatically validate the data send from the request to the `register` method. 

Used validation FormRequest is: `Binaryk\LaravelRestify\Http\Requests\RestifyRegisterRequest`

However if you want to validate the registration payload yourself you can disable the built in validation by nullifying `$registerFormRequest`:

```php
public function register(UserRegisterRequest $request)
{
    AuthService::$registerFormRequest = null;
    
    $user = $this->authService->register($request->only(array_keys($request->rules())));

    return Response::make(['data' => $user], 201);
}
```

Or you could simply override the `$registerFormRequest` with custom FormRequest:

```php
public function register(Request $request)
{
    AuthService::$registerFormRequest = UserRegisterRequest::class;
    
    $user = $this->authService->register($request->all());

    return Response::make(['data' => $user], 201);
}
```

- Exceptions

If something went wrong inside the register method, the AuthService will thrown few suggestive exceptions you can handle in the controller:

 > `Binaryk\LaravelRestify\Exceptions\PassportUserException` - Make sure your authenticatable entity (usually `User`) is implementing the `Binaryk\LaravelRestify\Contracts\Passportable` interface.
 
 > `\Binaryk\LaravelRestify\Exceptions\AuthenticatableUserException` - Make sure your authenticatable entity (usually `User`) is implementing the `Illuminate\Contracts\Auth\Authenticatable` interface.
 
 > `\Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException` - Class (usually `App\User`) defined in the configuration `auth.providers.users.model` could not been instantiated (may be abstract or missing at all)
    
- After successfully registering user, an `Illuminate\Auth\Events\Registered` event will be dispatched.

## Verifying users (optional)

This is an optional feature, but sometimes we may want users to validation the registered email.

- Prerequisites

Make sure `User` model implementing the `Illuminate\Contracts\Auth\MustVerifyEmail` contract.

The `MustVerifyEmail` contract will wait for a `sendEmailVerificationNotification` method definition. 

This method could look like this:

```php
// app/User.php

public function sendEmailVerificationNotification()
{
    $this->notify(new VerifyEmail);
}
```

The `VerifyEmail` should send the notification email to the user. This email should include two required data:
> the sha1 hash of the user email 

> user id

so your frontend application could easily make a verify call to the API with this data. 

Example of notification:

```php
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification
{
    /**
     * Get the notification's channels.
     *
     * @param mixed $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Verify Email Address'))
            ->line(Lang::get('Please click the button below to verify your email address.'))
            ->action(Lang::get('Verify Email Address'), $verificationUrl)
            ->line(Lang::get('If you did not create an account, no further action is required.'));
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param mixed $notifiable // the User entity in our case
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'register.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
```

As you may noticed it uses a route, let's scaffolding an verify route example as well:

```php
Route::get('email/verify/{id}/{hash}', 'AuthController@verify')
    ->name('register.verify')
    ->middleware([ 'signed', 'throttle:6,1' ]);
```

In a real life use case, the email content will look a bit different, because the `action` URL you want to send to the user
should match your frontend domain, not the API domain, and request to the API should be done from the frontend application.

- Next let's define the controller action:

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Binaryk\LaravelRestify\Services\AuthService;

class AuthController
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * This will mark the email verified if the email sha1 hash and user id matches
     * 
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException - thrown if hash or id doesn't match
     * @throws PassportUserException - thrown if the User don't implements Passportable
     * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException - thrown if the user not found
     */
    public function verify(Request $request)
    {
        $user = $this->authService->verify($request->route('id'), $request->route('hash'));

        return Response::make(['data' => $user]);
    }
}
```

- After verifying with success an `Illuminate\Auth\Events\Verified` event is dispatched.

## Login users (issue token)
After having user registered and verified (if the case) the API should be able to issue personal authorization tokens.

- Define a login route to an action controller:

```php
Route::post('login', 'AuthController@login');
```

- Inject the AuthService into your controller and call the login method:

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Binaryk\LaravelRestify\Services\AuthService;
use Binaryk\LaravelRestify\Exceptions\UnverifiedUser;
use Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch;
use Binaryk\LaravelRestify\Exceptions\PassportUserException;

class AuthController
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * This will validate the input, 
     * will register the user and return it back to the api
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $token = $this->authService->login($request->only('email', 'password'));

            return Response::make(compact('token'));
        } catch (CredentialsDoesntMatch | UnverifiedUser | PassportUserException $e) {
            return Response::make('Something went wrong.', 401);
        }
    }
}
```

The login method will thrown few exceptions:
> `Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch` - when email or password doesn't match

> `Binaryk\LaravelRestify\Exceptions\UnverifiedUser` - when `User` model implements `Illuminate\Contracts\Auth\MustVerifyEmail` 
and he did not verified the email

> `Binaryk\LaravelRestify\Exceptions\PassportUserException` - when `User` didn't implement `Binaryk\LaravelRestify\Contracts\Passportable`, the 
authenticatable entity should implement this contract, this way Restify will take the control over generating tokens.

- After login with success a personal token is issued and an `Binaryk\LaravelRestify\Events\UserLoggedIn` event is dispatched.

## Forgot password

Forgot password is the action performing by user in terms of recovering his lost password. Usually the API should send an email 
with a unique URL that allow users to reset password.

- Prerequisites:

If you want your users to be able to reset their passwords, make sure your `User` model implements the
`Illuminate\Contracts\Auth\CanResetPassword` contract.

This contract requires the `sendPasswordResetNotification` to be implemented. It could looks like this:

```php
/**
 * Send the password reset notification.
 *
 * @param string $token
 * @return void
 */
public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPassword($token));
}
```

Next let's define the `ResetPassword` notification. It should include a unique token, and should provide some information about the 
user email. The token will be resolved by the Laravel Restify and injected into your notification, so you don't have to worry about it:

```php
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * The token is generated by the Restify through the Broker class
     *
     * @param string $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @param mixed $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
    
        $frontendUrl = url(config('app.url') . '/reset-password?';

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
            ->action(Lang::get('Reset Password'), $frontendUrl . http_build_query(['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()])))
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire')]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }
```

The `getEmailForPasswordReset` method simply returns the user email:

```php
/**
 * @inheritDoc
 */
public function getEmailForPasswordReset()
{
    return $this->email;
}
```

- Define a forgot password route to an action controller:

```php
Route::post('password/email', 'AuthController@sendResetLinkEmail');
```

- Inject the AuthService into your controller and call the `sendResetPasswordLinkEmail` method:

```php
use Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException;use Binaryk\LaravelRestify\Exceptions\PasswordResetException;use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Binaryk\LaravelRestify\Services\AuthService;use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

     /**
      * This will validate the request input (email exists for example) and will send an reset passw
      * 
      * @param Request $request
      * @return JsonResponse
      */
     public function sendResetLinkEmail(Request $request)
     {
        try {
         $this->authService->sendResetPasswordLinkEmail($request->get('email'));
 
         return Response::make('', 204);
        } catch (EntityNotFoundException $e) {
            // Defined in the configuration auth.providers.users.model could not been instantiated (may be abstract or missing at all)
        } catch (PasswordResetException $e) {
            // Something unexpected from the Broker class
        } catch (ValidationException $e) {
            // The email is not valid
        }
     }
```

## Reset password

Finally we have to reset the users passwords. This can easily be done by using Restify AuthService as well.

- Define a reset password route to an action controller:

```php
Route::post('password/reset', 'AuthController@resetPassword')->name('password.reset');
```

- Inject the AuthService into your controller and call the resetPassword method:

```php
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Binaryk\LaravelRestify\Services\AuthService;

class AuthController
{
    /**
     * @var AuthService
     */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

   /**
    * @param Request $request
    * @return JsonResponse
    * @throws \Binaryk\LaravelRestify\Exceptions\Eloquent\EntityNotFoundException
    * @throws \Illuminate\Contracts\Container\BindingResolutionException
    * @throws \Illuminate\Validation\ValidationException
    */
   public function resetPassword(Request $request)
   {
       try {
           $this->authService->resetPassword($request);

           return Response::make(__('Password reset'));
       } catch (PasswordResetException|PasswordResetInvalidTokenException $e) {
           return Response::make('Something went wrong', 401);
       }
   }

```

After successfully password reset an `Illuminate\Auth\Events\PasswordReset` event is dispatched.
