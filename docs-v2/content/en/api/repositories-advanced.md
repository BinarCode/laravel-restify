---
title: Repositories advanced
menuTitle: Advanced
category: API 
position: 7
---

## Query Builder

To perform any request to the database, Restify has to create a query builder instance. The query builder is passed through a few static methods from the repository, so you can override them and intercept the builder to add your custom statements.

### Main query

The `mainQuery` method is called for ALL repository operations and serves as the base query that other query methods build upon. This is the foundational query method that's applied to `show`, `index`, `global search`, and all other requests:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function mainQuery(RestifyRequest $request, Builder | Relation $query)
{
    return $query->where('company_id', $request->user()->company_id);
}
```

This method is ideal for:
- **Global scoping** (e.g., multi-tenancy isolation)
- **Common filtering logic** that applies to all operations
- **Security constraints** that should never be bypassed
- **Global eager loading** for frequently used relationships

### Index query

The `indexQuery` method is specifically called for listing operations (`GET /api/restify/posts`) and global search requests. It builds on top of the `mainQuery`:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function indexQuery(RestifyRequest $request, Builder | Relation $query)
{
    return $query->where('status', 'published')
                 ->with('author:id,name')
                 ->orderBy('published_at', 'desc');
}
```

This method is perfect for:
- **Index-specific filtering** (e.g., only show published items)
- **Default sorting** for listings
- **Performance optimizations** for list views
- **Lightweight eager loading** for index displays

### Show query

The `showQuery` method is applied specifically for individual resource requests (`GET /api/restify/posts/1`). It allows you to customize queries when fetching a single resource:

```php
// PostRepository

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

public static function showQuery(RestifyRequest $request, Builder | Relation $query)
{
    return $query->with(['author', 'categories', 'tags', 'comments.user']);
}
```

This method is useful for:
- **Detailed eager loading** for full resource display
- **Show-specific constraints** or permissions
- **Performance optimizations** for single resource fetching

### Scout query

When using Laravel Scout for full-text search, you can customize the Scout query builder:

```php
// PostRepository

public static function scoutQuery(RestifyRequest $request, $scoutBuilder)
{
    return $scoutBuilder->where('status', 'published')
                       ->where('tenant_id', $request->user()->tenant_id);
}
```

### Query Method Hierarchy

The query methods are applied in this specific order:

1. **Base Query**: `query()` - Creates the initial query builder from the model
2. **Main Query**: `mainQuery()` - Applied to ALL operations for global constraints
3. **Specific Query**: `indexQuery()` or `showQuery()` - Applied based on the operation type
4. **Search/Filters**: Applied by Restify's search service based on request parameters
5. **Scout Query**: `scoutQuery()` - Only applied when using Laravel Scout search

### Complete Example: Multi-tenant Blog Repository

```php
class PostRepository extends Repository
{
    // Step 2: Applied to ALL operations - ensures tenant isolation
    public static function mainQuery(RestifyRequest $request, $query)
    {
        return $query->where('tenant_id', $request->user()->tenant_id)
                     ->whereNull('deleted_at'); // Global soft delete check
    }
    
    // Step 3a: Only for listing - show published posts with minimal data
    public static function indexQuery(RestifyRequest $request, $query)
    {
        return $query->where('status', 'published')
                     ->with('author:id,name,avatar')
                     ->orderBy('published_at', 'desc');
    }
    
    // Step 3b: Only for individual posts - load complete relationships
    public static function showQuery(RestifyRequest $request, $query)
    {
        return $query->with([
            'author',
            'categories',
            'tags',
            'comments' => function ($query) {
                $query->where('approved', true)->with('user:id,name');
            }
        ]);
    }
    
    // Step 5: Scout search within tenant boundaries
    public static function scoutQuery(RestifyRequest $request, $scoutBuilder)
    {
        return $scoutBuilder->where('tenant_id', $request->user()->tenant_id)
                           ->where('status', 'published');
    }
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

Thus, Restify generates the URI for the repository in the following way:

```php
config('restify.base') . '/' . UserRepository::uriKey() . '/'
```

For example, let's assume we have the `restify.base` equal with: `api/restify`. The default URI generated for the
UserRepository is:

```http request
GET: /api/restify/users
```

However, you can prefix the repository with your own:

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

Important: Don't forget to call the parent `constructor`.

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
the entire logic of a specific action. Let's say your `save` method needs to perform additional operations beyond the default action. In
this case you can easily override each action ([defined here](/repositories#actions-handled-by-the-repository)) in the repository:

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

Let's examine a more practical example. Let's use the Post repository we defined above:

<alert>

Route wrapping: The `$wrap` argument determines whether your route should be wrapped with the default `middlewares`,
`controllers namespace`, and `prefix` your routes with the repository's base (i.e., `/api/restify/posts/`).

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

As we saw in the example above, the route is a child of the current repository. However, you might want to
have a separate prefix occasionally, which could be outside the URI of the current repository. Restify provides an easy way to do this by
adding a default value `prefix` for the second `$attributes` argument:

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

All routes declared in the `routes` method will have the same middlewares defined in your `restify.middleware`
configuration file. Overriding default middlewares is straightforward with Restify:

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

Non-wrapped routes: When `$wrap` is false, your routes will only have the Route group `$attributes`, which means that no
prefix, middleware, or namespace will be applied automatically, even if you defined them as default arguments in
the `routes` method. You should be careful about this behavior.

</alert>


## Repository Lifecycle

Each repository has several lifecycle methods. The most useful is `booted`, which is called as soon as the repository is loaded with the resource:

````php
// PostRepository.php
protected static function booted()
{
    // 
}
````




