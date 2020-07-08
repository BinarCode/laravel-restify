# Repository

[[toc]]

## Introduction

The Repository is the core of the Laravel Restify, included with Laravel provides an easy way of 
managing (usually called "CRUD") your resources. 

## Quick start
The follow command will generate the Repository which will take the control over the post resource.

```shell script
php artisan restify:repository PostRepository
```

The newly created repository will be placed in the `app/Restify/PostRepository.php` file.

## Defining Repositories

```php

use App\Restify\Repository;

class PostRepository extends Repository
{
    /**
     * The model the repository corresponds to.
     *
     * @var string
     */
    public static $model = 'App\\Models\\Post';
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
| POST           | `/restify-api/posts/bulk`     | store multiple   |
| POST           | `/restify-api/posts/bulk/update`     | store multiple   |
| PATCH          | `/restify-api/posts/{post}`   | update  |
| PUT            | `/restify-api/posts/{post}`   | update  |
| POST           | `/restify-api/posts/{post}`   | update  |
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


## Repository middleware

Each repository has the `restify.middleware` out of the box for the CRUD methods. However, you're free to add your own middlewares for a specific repository.

```php
    // PostRepository.php

    public static $middlewares = [
        NeedsCompanyMiddleware::class,
    ];

```

This `NeedsCompanyMiddleware` is a custom middleware, and it will be applied over all CRUD routes for this repository.

If you need the current request, you can override the `collectMiddlewares` method, and use the current request:

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

## Dependency injection

The Laravel [service container](https://laravel.com/docs/7.x/container) is used to resolve all Laravel Restify repositories. 
As a result, you are able to type-hint any dependencies your `Repository` may need in its constructor. 
The declared dependencies will automatically be resolved and injected into the repository instance:

:::tip
Don't forget to call the parent `contructor`
:::

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

## Restify Repository Conventions
Let diving deeper into the repository, and take step by step each of its available pieces and customizable 
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
public static $model = 'App\\Models\\Post'; 
```

## CRUD Methods overriding 

Laravel Restify magically made all "CRUD" operations for you. However, sometimes you may want to intercept, or override the
entire logic of a specific action. Let's say your `save` method has to do something else besides action itself. In this case you can easily override each action ([defined here](#actions-handled-by-the-repository)) from the repository:

### index

```php
    public function index(Binaryk\LaravelRestify\Http\Requests $request)
    {
        // Silence is golden
    }
```

### show

```php
    public function show(Binaryk\LaravelRestify\Http\Requests $request, $repositoryId)
    {
        // Silence is golden
    }
```

### store

```php
    public function store(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request)
    {
        // Silence is golden
    }
```

### store bulk

```php
    public function storeBulk(Binaryk\LaravelRestify\Http\Requests\RepositoryStoreBulkRequest $request)
    {
        // Silence is golden
    }
```

### update

```php
    public function update(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request, $repositoryId)
    {
        // Silence is golden
    }
```

### update bulk

// $row is the payload row to be updated

```php
    public function updateBulk(RestifyRequest $request, $repositoryId, int $row)
    {
        // Silence is golden
    }
```

### destroy

```php
    public function destroy(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request, $repositoryId)
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

This response is accordingly to [JSON:API format](https://jsonapi.org/format/). You can change it for all 
repositories at once by modifying the `serializeForShow` method of the abstract Repository, or for a specific
repository by overriding it:

```php
/**
 * Resolve the response for the details.
 *
 * @param $request
 * @param $serialized
 * @return array
 */
public function serializeForShow(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request): array
{
    // your own format
    return [
        'title' => $this->resource->title,
    ];
}
```

You can change the index response by modifying the `resolveIndex` method:

```php
/**
 * Resolve the response for the index.
 *
 * @param $request
 * @param $serialized
 * @return array
 */
public function serializeForIndex(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request): array
{
    // your own format
    return [
        'title' => $this->resource->title,
    ];
}
```

### Index meta

Index request returns a list with `meta` attribute. By default, this includes the permissions over the current list item. 
You can customize the `meta` attributes adding `resolveIndexMeta`:

```php
public function resolveIndexMeta($request)
{
$default = parent::resolveIndexMeta($request);
    return array_merge($default, [
        'next_payment_at' => $this->resource->current_payment_at->addMonth(),
    ]);
}
```

### Index main meta

You can also override the main `meta` object for the index, not the one for per item:

```php
public function resolveIndexMainMeta(RestifyRequest $request, Collection $items, array $paginatorMeta): array
{
    return array_merge($paginatorMeta, [
        'next_payment_at' => $this->resource->current_payment_at->addMonth(),
    ]);
}
```

### Serialize show

As well as for the index items, you can add custom attributes or change the format for the show request resolved by `/resource/{resourceKey}`:

```php
public function resolveForShow($repository, $attribute = null)
{
    //    
}
```

### Show Meta

You are free to customize the show meta information as well by defining the `resolveShowMeta` method:

```php
public function resolveShowMeta($request)
{
    //    
}
```


## Merge all model attributes

Laravel Restify will show and fill only attributes defined in the `fields` method of the repository. However, you can merge all of the model attributes by implementing `Mergeable` contract:

```php

use Binaryk\LaravelRestify\Repositories\Mergeable;

class PostRepository extends Repository implements Mergeable
{
    //
}
```

Even if you didn't specify any field in your `fields` method, the show/index requests will always return all the model attributes in this case. 

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
 * However all options could be overrided by passing an $attributes argument and set $wrap to false
 *
 * @param  \Illuminate\Routing\Router  $router
 * @param $attributes
 */
public static function routes(\Illuminate\Routing\Router $router, $attributes = [], $wrap = true)
{
    $router->get('last-posts', function () {
        return static::newModel()->latest()->first();
    });

    $router->post('make-primary/{post}', [static::class, 'makePrimary']);
}

public function makePrimary(Post $post) 
{
    // Handle         
    // ...
    return $this->response()->forRepository($this);
}
```

Lets diving into a more "real life" example. Let's take the Post repository we had above:

:::tip
The `$wrap` argument is the one who says to your route to be wrapped in the default middlewares, controllers namespace and 
prefix your routes with the base of the repository (ie `/restify-api/posts/`).
:::

```php
use Illuminate\Routing\Router;
use App\Restify\Repository;

class PostRepository extends Repository
{
   public static function routes(\Illuminate\Routing\Router $router, $attributes = [], $wrap = true)
   {
       $router->get('/{id}/kpi', 'PostController@kpi'); // /restify-api/posts/1/kpi
   }
}
```

At this moment Restify built the new route as a child of the `posts`, so it has the route:

```http request
GET: /restify-api/posts/{id}/kpi
```

This route is pointing to the `PostsController@kpi`, let's define it:

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

### Route prefix

As we noticed in the example above, the route is a child of the current repository,
however sometimes you may want to have a separate prefix, which is out of the URI of the current repository. 
Restify provide you an easy of doing that, by adding default value `prefix` for the second `$attributes` argument:

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

Now the generated route will look like this:

```http request
GET: '/api/hello-world
```

With `api` as a custom prefix. 

### Route middleware

All routes declared in the `routes` method, will have the same middelwares defined in your `restify.middleware` configuration file.
Overriding default middlewares is a breeze with Restify:

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

By default, each route defined in the `routes` method, will have the namespace `AppRootNamespace\Http\Controllers`.
You can override it easily by using `namespace` configuration key:

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

:::warning Clean routes
If `$wrap` is false, your routes will have any Route group `$attributes`, that means no prefix, middleware, or namespace will be applied out of the box, even you defined that as a default argument in the `routes` method. So you should take care of that.
:::

## Attach related 

- Attach related models to a model (check tests)
- Attach multiple related model to a model
- Attach extra information to the pivot

Example of how to attach users posts to users with `is_owner` extra pivot:
```javascript
axios.post('restify-api/users/1/attach/posts', [
    'posts': [1, 2],
    'is_owner': true
])
```

## Detach related

- Detach repository
- Detach multiple repositories

Example of how to remove posts from user:

```javascript
axios.post('restify-api/users/1/detach/posts', [
    'users': [1, 2]
]);
```

## Write your own attach

If you want to implement attach method for such relationship on your own, Laravel Restify provides you an easy way of doing that. Let's say you have to attach roles to user:

```php
// app/Restify/UserRepository.php
public function attachRoles(RestifyRequest $request, UserRepository $repository, User $user)
{
    $roles = collect($request->get('roles'))->map(fn($role) => Role::findByName($role, 'web'));

    if ($id = $request->get('company_id')) {
        $user->assignCompanyRoles(
            Company::find($id),
            $roles
        );
    }

    return $this->response()->created();
}
```
The javascript request will remain the same:

```javascript
axios.post('restify-api/users/1/attach/roles', [
    'roles': [1, 2]
])
```

Based on your related resource, `roles`, Laravel Restify will automatically detect the `attachRoles` method.

If you don't like this kind of `magic` stuff, you can override the `getAttachers` method, and return an associative array, where the key is the name of the related resource, and the value should be a closure which  handle the action:

```php
public static function getAttachers(): array
{
    'roles' => function(RestifyRequest $request, UserRepository $repository, User $user) {
        //
    },
}
```

## Force eager loading

However, Laravel Restify [provides eager](/search/) loading based on the query `related` property, 
you may want to force eager load a relationship in terms of using it in fields, or whatever else: 

```php
// UserRepository.php

public static $with = ['posts'];
```

## Store bulk flow

However, the `store` method is a common one, the `store bulk` requires a bit of attention. 

### Bulk field validations

Similar with `store` and `update` methods, `bulk` rules has their own field rule definition: 

```php
->storeBulkRules('required', function () {}, Rule::in('posts:id'))
```

The validation rules will be merged with the rules provided into the `rules()` method. The validation will be performed 
by using native Laravel validator, so you will have exactly the same experience. The validation `messages` could still be used as usual. 

### Bulk Payload

The payload for a bulk store should contain an array of objects: 

```http request
POST: /restify-api/posts/bulk/update
```

Payload:

```json
[
  {
  "title": "First post"
  },
  {
  "title": "Second post"
  }
]
```

### Bulk after store 

After storing an entity, the repository will call the static `bulkStored` method from the repository, so you can override:

```php
public static function storedBulk(Collection $repositories, $request)
{
    //
}
```

## Update bulk flow

As the store bulk, the update bulk uses DB transaction to perform the action. So you can make sure that even all entries, even no one where updated.

### Bulk update field validations

```php
->updateBulkRules('required', Rule::in('posts:id'))
```

Sometimes you may need to know the current row in `fieldsForStoreBulk` for some complex validations, this will be passed for bulk update and store:

```php
public function fieldsForStoreBulk(RestifyRequest $request, int $bulkRow)
{
return [
    Field::new()->rules(Rule::unique('users')->where(function ($query) use ($request, $bulkRow) {
                            return $query->where('account_id', data_get($request->all(), $bulkRow . '.account_id'));
                        }))
];
}
```

### Bulk Payload

The payload for a bulk update should contain an array of objects. Each object SHOULD contain an `id` key, based on this, the Laravel Restify will find the entity: 

```http request
POST: /restify-api/posts/bulk/update
```

Payload: 

```json
[
  {
  "id": 1,
  "title": "First post"
  },
  {
  "id": 2,
  "title": "Second post"
  }
]
```

