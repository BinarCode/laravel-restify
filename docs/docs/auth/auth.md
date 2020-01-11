# Authentication setup

Laravel Restify the authentication implemented with Passport, so you can use it out of the box. 
You'll finally enjoy the auth setup (`register`, `login`, `forgot` and `reset password`).

:::tip

First make sure you have installed and configured the Laravel Passport properly. 
This can be done easily by using the follow Restify command: 

`php artisan restify:check-passport`

This command will become with suggestions if anything is setup wrong.
:::

## Prerequisites
- When using the Restify authentication service, you will need to migrate the users table.
- Make sure your authenticatable entity (usually `User`) is implementing the `Binaryk\LaravelRestify\Contracts\Passportable` interface.
- Assure that `restify:check-passport` passes with success.


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
     * @param UserRegisterRequest $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        $user = $this->authService->register($request->all());

        return Response::make(['data' => $user], 201);
    }
}
```

After registering user, an `Illuminate\Auth\Events\Registerd` event will be dispatched.

## Verifying users (optional)

If you want your users to verify their email you have to make the `User` model implementing the `Illuminate\Contracts\Auth\MustVerifyEmail` contract.


## Login users (issue token)

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
     * @param UserRegisterRequest $request
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
- `Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch` - when email or password doesn't match
- `Binaryk\LaravelRestify\Exceptions\UnverifiedUser` - when `User` model implements `Illuminate\Contracts\Auth\MustVerifyEmail` 
and he did not verified the email
- `Binaryk\LaravelRestify\Exceptions\PassportUserException` - when `User` didn't implement `Binaryk\LaravelRestify\Contracts\Passportable`, the 
authenticatable entity should implement this contract, this way Restify will take the control over generating tokens.

After login with success a personal token is issued and an `Binaryk\LaravelRestify\Events\UserLoggedIn` event is dispatched.

## 
