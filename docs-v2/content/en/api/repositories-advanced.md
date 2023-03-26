---
title: Repositories advanced
menuTitle: Advanced
category: API 
position: 7
---

## Query Builder

To perform any request to the database, Restify has to create a query builder instance. The query builder is passed through a few static methods from the repository, so you can override them and intercept the builder to add your custom statements.

### Main query

The `main` query is applied for `show`, `index` and `global search` requests. You can override it in the repository:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function mainQuery(RestifyRequest $request, Builder | Relation $query)
{
    //
}
```


### Index query


The `index` query is applied for `index` and `global search` requests. You can override it in the repository:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function indexQuery(RestifyRequest $request, Builder | Relation $query)
{
    //
}
```

### Show query


The `show` query is applied for the `show` request. You can override it in the repository:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function showQuery(RestifyRequest $request, Builder | Relation $query)
{
    //
}
```

## Repository prefix

The default prefix of all Restify routes (except `login` and `register`) lives under the `restify->base` config:

```php
// config/restify.php
...
'base' => '/api/restify',
...
```

Tjus, Restify generates the URI for the repository in the following way:

```php
config('restify.base') . '/' . UserRepository::uriKey() . '/'
```

For example, let's assume we have the `restify.base` equal with: `api/restify`. The default URI generated for the
UserRepository is:

```http request
GET: /api/restify/users
```

Nonetheless, you can prefix the repository with your own:

```php
// UserRepository
public static $prefix = 'api/v1';
```

Now, the generated URI will look like this:

```http request
GET: /api/v1/users
```

<alert>

For the rest of the repositories the prefix will stay as it is, the default one. Keep in mind that this custom prefix
will be used for all the endpoints related to the user repository.

</alert>

## Repository middleware

Each repository has the middlewares from the config `restify.middleware` out of the box for the CRUD methods. However,
you're free to add your own middlewares for a specific repository.

```php
    // PostRepository.php

    public static $middleware = [
        NeedsCompanyMiddleware::class,
    ];

```

This `NeedsCompanyMiddleware` is a custom middleware, and it will be applied over all CRUD routes for this repository.

If you need the current request, you can override the `collectMiddlewares` method and use the current request:

```php
public static function collectMiddlewares(RestifyRequest $request): ?Collection
{
    if ($request->isShowRequest())         
    {
        return collect([ 
            NeedsCompanyMiddleware::class,
        ]);
    }

    if ($request->isIndexRequest())         
    {
        return collect([ 
            SampleIndexRequest::class,
        ]);
    }

    return null;
}
```

## Repository registration

Laravel Restify registers all repositories automatically in the App namespace. However, you can register your own repositories from any service provider using the InteractsWithRestifyRepositories trait. Here's an example:

```php
<?php

namespace MyPackage\Cart;

use Binaryk\LaravelRestify\Traits\InteractsWithRestifyRepositories;
use Illuminate\Support\ServiceProvider;

class MyPackageCart extends ServiceProvider
{
    use InteractsWithRestifyRepositories;

    public function register(): void
    {
        $this->loadRestifyFrom(__DIR__.'/Restify', __NAMESPACE__.'\\Restify\\');
        
        // The rest of your package's registration code goes here.
    }
}
```

If you want to load Restify from your own service provider, you must use the InteractsWithRestifyRepositories trait in the service provider class. The loadRestifyFrom method takes the path to the directory containing the repositories and the namespace under which the repositories will be registered.

## Dependency injection

The Laravel [service container](https://laravel.com/docs/7.x/container) is used to resolve all the Laravel Restify
repositories. As a result, you are able to type-hint any dependencies your `Repository` may need in its constructor. The
declared dependencies will automatically be resolved and injected into the repository's instance:

<alert> 

Parent Don't forget to call the parent `constructor`.

</alert>

```php
use App\Services\PostService;
use App\Restify\Repository;

class PostRepository extends Repository
{
   private PostService $postService; 

   public function __construct(PostService $service)
   {
       parent::__construct();

       $this->postService = $service;
   }
}
```

### Custom CRUD

Restify injects all `CRUD`'s operations for you. However, sometimes you may want to intercept or override
the entire logic of a specific action. Let's say your `save` method has to do something else besides the action itself. In
this case you can easily override each action ([defined here](#actions-handled-by-the-repository)) from the repository:

### index

```php
    public function index(RestifyRequest $request)
    {
        // Silence is golden
    }
```

### show

```php
    public function show(RestifyRequest $request, $repositoryId)
    {
        // Silence is golden
    }
```

### store

```php
    public function store(RestifyRequest $request)
    {
        // Silence is golden
    }
```

### store bulk

```php
    public function storeBulk(RepositoryStoreBulkRequest $request)
    {
        // Silence is golden
    }
```

### update

```php
    public function update(RestifyRequest $request, $repositoryId)
    {
        // Silence is golden
    }
```

### update bulk

```php
    // $row is the payload row to be updated
    public function updateBulk(RestifyRequest $request, $repositoryId, int $row)
    {
        // Silence is golden
    }
```

### destroy

```php
    public function destroy(RestifyRequest $request, $repositoryId)
    {
        // Silence is golden
    }
```

## Custom routes

Laravel Restify has its own "CRUD" routes, although you're able to define your custom routes right from your Repository
class:

```php
/**
 * Defining custom routes
 * 
 * The default prefix of this route is the uriKey (e.g. 'api/restify/posts'),
 * 
 * The default namespace is AppNamespace/Http/Controllers
 * 
 * The default middlewares are the same from config('restify.middleware')
 *
 * All options could be overrided by passing an $attributes argument and set $wrap to false
 *
 * @param  \Illuminate\Routing\Router  $router
 * @param $attributes
 */
public static function routes(\Illuminate\Routing\Router $router, $attributes = [], $wrap = true)
{
    $router->get('last-posts', function () {
        return static::makeModel()->latest()->first();
    });

    $router->post('make-primary/{post}', [static::class, 'makePrimary']);
}

public function makePrimary(Post $post) 
{
    // Handle         
    // ...
    return response('Done');
}
```

Let's dive into a more "real life" example, shall we?. Let's take the Post repository we had above:

<alert>

Route wrap The `$wrap` argument is the one that tells your route to be wrapped in the default `middlewares`
, `controllers namespace`, and `prefix` your routes with the base of the repository (ie `/api/restify/posts/`).

</alert>

```php
use App\Restify\Repository;

class PostRepository extends Repository
{
   public static function routes(\Illuminate\Routing\Router $router, $attributes = [], $wrap = true)
   {
       $router->get('/{id}/kpi', 'PostController@kpi'); // /api/restify/posts/1/kpi
   }
}
```

At this moment Restify is building the new route as a child of the `posts`, so it has the following route for instance:

```http request
GET: /api/restify/posts/{id}/kpi
```

This route is pointing to the `PostsController@kpi`. Let's define it:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Binaryk\LaravelRestify\Controllers\RestController;

class PostController extends RestController
{
    /**
     * Show the kpi for the given user.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function kpi($id)
    {
        //...

        return $this->response();
    }
}
```

### Route prefix

As we noticed in the example above, the route is a child of the current repository. However, you might want to
have a separate prefix from time to time, which could be out of the URI of the current repository. Restify provide you an easy way of doing that by
adding default value `prefix` for the second `$attributes` argument:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $options
 */
public static function routes(Router $router, $attributes = ['prefix' => 'api',], $wrap = true)
{
    $router->get('hello-world', function () {
        return 'Hello World';
    });
}
````

Now, the generated route will look like this:

```http request
GET: '/api/hello-world
```

With `api` as a custom prefix.

### Route middleware

All routes declared in the `routes` method, will have the same middelwares defined in your `restify.middleware`
configuration file. Overriding default middlewares is a breeze with Restify:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $attributes
 */
public static function routes(Router $router, $attributes = ['middleware' => [CustomMiddleware::class],], $wrap = true)
{
    $router->get('hello-world', function () {
        return 'Hello World';
    });
}
````

In that case, the single middleware of the route will be defined by the `CustomMiddleware` class.

### Route Namespace

By default, each route defined in the `routes` method will have the namespace `AppRootNamespace\Http\Controllers`. You
can override it easily by using `namespace` configuration key:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $attributes
 */
public static function routes(Router $router, $attributes = ['namespace' => 'App\Services',], $wrap = true)
{
    $router->get('hello-world', 'WorldController@hello');
}
````

<alert type="warning">

Non wrapped route Clean routes if `$wrap` is false. Your routes will basically have any Route group `$attributes`, which means that no
prefix, middleware, or namespace will be applied out of the box, even if you defined it as a default argument in
the `routes` method. You should be very attentive to that.

</alert>


## Repository Lifecycle

Each repository has a few lifecycles. The most useful is `booted`, it is called as soon the repository is loaded with the resource:

````php
// PostRepository.php
protected static function booted()
{
    // 
}
````




