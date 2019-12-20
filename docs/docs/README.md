# Installation

[[toc]]

## Requirements

Laravel Restify has a few requirements you should be aware of before installing:

- Composer
- Laravel Framework 5.5+

## Installing Laravel Restify

```bash
composer require binaryk/laravel-restify
```

:::tip Package Stability

If you are not able to install Restify into your application because of your `minimum-stability` setting, consider setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. This will allow you to install Laravel restify while still preferring stable package releases for your application.
:::

That's it! 

Next, you may extend the `Binaryk\LaravelRestify\Controllers\RestController` and use its helpers:

```php
class UserController extends RestController 
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $users = User::all();

        return $this->respond($users);
    }
}
```

## Authentication service

For each API we have to implement the auth module from scratch. With Laravel Restify this is not a pain anymore:

```php
class AuthController extends Binaryk\LaravelRestify\Controllers\RestController
{
    /**
         * @var Binaryk\LaravelRestify\Services\AuthService
         */
        protected $authService;
    
        public function __construct(Binaryk\LaravelRestify\Services\AuthService $authService)
        {
            $this->authService = $authService;
        }
    
        /**
        * @param RestifyLoginRequest $request
        * @return Illuminate\Http\JsonResponse|string|void
        * @throws \Binaryk\LaravelRestify\Exceptions\CredentialsDoesntMatch
        * @throws \Binaryk\LaravelRestify\Exceptions\PassportUserException
        * @throws \Binaryk\LaravelRestify\Exceptions\UnverifiedUser
        */
        public function login(RestifyLoginRequest $request)
        {
            $credentials = $request->only('email', 'password');
            $response = $this->response();
    
            try {
                $token = $this->authService->login($credentials);
    
                $response->data(['token' => $token])->message(__('Authentication with success'));
            } catch (CredentialsDoesntMatch | UnverifiedUser | PassportUserException $e) {
                $response->addError($e->getMessage())->auth();
            }
    
            return $response->respond();
        }
}
```

