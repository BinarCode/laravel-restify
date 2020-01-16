# Repository

[[toc]]

## Introduction

The Repository is the main core of the Laravel Restify, included with Laravel provides the an easy way of 
managing (usually called "CRUD"). It works along with 
[Laravel API Resource](https://laravel.com/docs/6.x/eloquent-resources), 
that means you can use all helpers from there right away.

## Quick start
The follow command will generate you the Repository which will take the control over the post resource.

```shell script
php artisan restify:repository Post
```

The newly created repository could be found in the `app/Restify` directory.

## Defining Repositories

```php

use Binaryk\LaravelRestify\Repositories\Repository;

class Post extends Repository
{
    /**
     * The model the repository corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Post';
}
```

### Actions handled by the Repository

Having this in place you're basically ready for the CRUD actions over posts. 
You have available the follow endpoints:

| Verb          | URI                            | Action  | 
| :------------- |:----------------------------- | :-------|
| GET            | `/restify-api/posts`          | index   |
| GET            | `/restify-api/posts/{post}`   | show    |
| POST           | `/restify-api/posts`          | store   |
| PATCH          | `/restify-api/posts/{post}`   | update  |
| DELETE         | `/restify-api/posts/{post}`   | destroy |

### Fields
When storing or updating a model - Restify will get from the request all of the attributes matching by key
with those from the `fillable` array of the model definition. 
Restify will fill these fields with the value from the request.
However if you want to transform some attributes before they are filled into the model
you can do that by defining the `fields` method:

```php
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class Post extends Repository
{
    /**
     * The model the repository corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Post';

  /**
     * Resolvable attributes before storing/updating
     * 
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request) 
    {
        return [
            Field::make('title')->storeCallback(function ($requestValue) {
                return is_string($requestValue) ? $requestValue : `N/A`; 
            })  
        ];
    }
}
```

:::tip

`Field` class has many mutations, validators and interactions you can use, these are documented [here](/laravel-restify/docs/repository-pattern/field)

:::


## Dependency injection

The Laravel [service container](https://laravel.com/docs/6.x/container) is used to resolve all Laravel Restify repositories. 
As a result, you are able to type-hint any dependencies your `Repository` may need in its constructor. 
The declared dependencies will automatically be resolved and injected into the repository instance:

:::tip
Don't forget to to call the parent `contructor`
:::

```php
use Binaryk\LaravelRestify\Repositories\Repository;

class Post extends Repository
{
   /**
    * The model the repository corresponds to.
    *
    * @var string
    */
   public static $model = 'App\\Post'; 

    /**
    * @var PostService 
    */
   private $postService; 

   /**
    * Post constructor.
    * @param PostService $service
    */
   public function __construct(PostService $service)
   {
       parent::__construct();
       $this->postService = $service;
   }

}
```

## Restify Repository Conventions
Let's diving deeper into the repository, and take step by step each of its available tools and customizable 
modules. Since this is just a helper, it should not break your normal development flow.

### Model name
As we already noticed, each repository basically works as a wrapper over a specific resource. 
The fancy naming `resource` is nothing more than a database entity (posts, users etc.). Well, to make the 
repository aware of the entity it should take care of, we have to define the model property: 

```php
/**
* The model the repository corresponds to.
*
* @var string
*/
public static $model = 'App\\Post'; 
```

## CRUD Methods overriding 

Laravel Restify magically made all "CRUD" operations for you. But sometimes you may want to intercept, or override the
entire logic of a specific action. Let's say your `save` method has to do something different than just storing
the newly created entity in the database. In this case you can easily override each action ([defined here](#actions-handled-by-the-repository)) from the repository:

### index

```php
    public function index(RestifyRequest $request, Paginator $paginated)
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
    /**
     * @param  RestifyRequest  $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function store(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request)
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

### destroy

```php
    public function destroy(RestifyRequest $request, $repositoryId)
    {
        // Silence is golden
    }
```

## Transformation layer

When you call the `posts/{post}` endpoint, the repository will return  the following primary 
data for a single resource object:

```json
{
  "data": {
    "type": "post",
    "id": "1",
    "attributes": {
      // ... this post's attributes
    },
    "meta": {
      // ... by default meta includes information about user authorizations over the entity
      "authorizedToView":  true,
      "authorizedToCreate": true,
      "authorizedToUpdate":  true,
      "authorizedToDelete": true
    } 
  }
}
```

This response is made according to [JSON:API format](https://jsonapi.org/format/). You can change it for all 
repositories at once by modifying the `resolveDetails` method of the abstract Repository, or for a specific
repository by overriding it:

```php
/**
 * Resolve the response for the details
 *
 * @param $request
 * @param $serialized
 * @return array
 */
public function serializeDetails($request, $serialized)
{
    return $serialized;
}
```

You can change the index response by modifying the `resolveIndex` method:

```php
/**
 * Resolve the response for the details
 *
 * @param $request
 * @param $serialized
 * @return array
 */
public function serializeIndex($request, $serialized)
{
    return $serialized;
}
```

## Custom routes

Laravel Restify has its own "CRUD" routes, however you're able to define your own routes right from your Repository class:

```php
/**
 * Defining custom routes
 * 
 * The default prefix of this route is the uriKey (e.g. 'restify-api/posts'),
 * 
 * The default namespace is AppNamespace/Http/Controllers
 * 
 * The default middlewares are the same from config('restify.middleware')
 *
 * However all options could be overrided by passing an $options argument
 *
 * @param  \Illuminate\Routing\Router  $router
 * @param $options
 */
public static function routes(\Illuminate\Routing\Router $router, $options = [])
{
    $router->get('hello-world', function () {
        return 'Hello World';
    });
}
```

Let's diving into a more "real life" example. Let's take the Post repository we had above:

```php
use Illuminate\Routing\Router;
use Binaryk\LaravelRestify\Repositories\Repository;

class Post extends Repository
{
   /*
   * @param  \Illuminate\Routing\Router  $router
   * @param $options
   */
   public static function routes(Router $router, $options = [])
   {
       $router->get('/{id}/kpi', 'PostController@kpi');
   }
       
   public static function uriKey()
   {
       return 'posts';
   }
}
```

At this moment Restify built the new route as a child of the `posts`, so it has the route:

```http request
GET: /restify-api/posts/{id}/kpi
```

This route is pointing to the `PostsController`, let's define it:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Binaryk\LaravelRestify\Controllers\RestController;

class PostController extends RestController
{
    /**
     * Show the profile for the given user.
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

### Custom prefix

As we noticed in the example above, the route is generated as a child of the current repository `uriKey` route,
however sometimes you may want to have a separate prefix, which doesn't depends of the URI of the current repository. 
Restify provide you an easy of doing that, by adding default value `prefix` for the second `$options` argument:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $options
 */
public static function routes(Router $router, $options = ['prefix' => 'api',])
{
    $router->get('hello-world', function () {
        return 'Hello World';
    });
}
````

Now the generated route will look like this:

```http request
GET: '/api/hello-world
```

With `api` as a custom prefix. 


### Custom middleware

All routes declared in the `routes` method, will have the same middelwares defined in your `restify.middleware` configuration file.
Overriding default middlewares is a breeze with Restify:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $options
 */
public static function routes(Router $router, $options = ['middleware' => [CustomMiddleware::class],])
{
    $router->get('hello-world', function () {
        return 'Hello World';
    });
}
````

In that case, the single middleware of the route will be defined by the `CustomMiddleware` class.

### Custom Namespace

By default each route defined in the `routes` method, will have the namespace `AppRootNamespace\Http\Controllers`.
You can override it easily by using `namespace` configuration key:

```php
/**
 * @param  \Illuminate\Routing\Router  $router
 * @param $options
 */
public static function routes(Router $router, $options = ['namespace' => 'App\Services',])
{
    $router->get('hello-world', 'WorldController@hello');
}
````
