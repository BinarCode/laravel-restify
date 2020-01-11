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

## Setup Laravel Restify
After the instalation, the package requires a setup process, this will publish the provider, will create the 
`app/Restify` directory with an abstract `Repository` and scaffolding a `User` repository you can play with:

```shell script
php artisan restify:setup
```

:::tip Package Stability

If you are not able to install Restify into your application because of your `minimum-stability` setting,
 consider setting your `minimum-stability` option to `dev` and your `prefer-stable` option to `true`. 
 This will allow you to install Laravel Restify while still preferring stable package 
 releases for your application.
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

