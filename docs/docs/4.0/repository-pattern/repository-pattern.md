# Repository

The Repository is the core of the Laravel Restify. In here you can define your logic for the model attributes
manipulations for your CRUD operations.

## Quick start

The follow command will generate the Repository which will manage `CRUD` for the `Post` model.

```shell script
php artisan restify:repository PostRepository --all
```

The newly created repository will be placed in the `app/Restify/PostRepository.php` file.

The `--all` argument will also create the migrations, policy and model (in `app/Models`), however you can ignore
the `--all` argument and create these manually.

## Defining Repositories

The minimum repository definition will look like this.

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
    
     /**
     * Resolvable attributes.
     *
     * @param RestifyRequest $request
     * @return array
     */
    public function fields(RestifyRequest $request)
    {
        return [];
    }
}
```

### Actions handled by the Repository

Having this in place you're basically ready for the CRUD actions over posts. You have available the follow endpoints:

| Verb          | URI                            | Action  |
| :------------- |:----------------------------- | :-------|
| GET            | `/api/restify/posts`          | index   |
| GET            | `/api/restify/posts/{post}`   | show    |
| POST           | `/api/restify/posts`          | store   |
| POST           | `/api/restify/posts/bulk`     | store multiple   |
| POST           | `/api/restify/posts/bulk/update`     | store multiple   |
| PATCH          | `/api/restify/posts/{post}`   | update  | 
| PUT            | `/api/restify/posts/{post}`   | update  |
| POST           | `/api/restify/posts/{post}`   | update  | 
| DELETE         | `/api/restify/posts/{post}`   | destroy |

:::tip Update with files As you can see we provide 3 Verbs for the model update (PUT, PATCH, POST), the reason of that
is because you cannot send files via `PATCH` or `PUT` verbs, so we have `POST`. Where the `PUT` or `PATCH` could be used
for full model update and respectively partial update.
:::

## Model name

As we already noticed, each repository basically works as a wrapper over a specific resource. The fancy
naming `resource` is nothing more than a database entity (posts, users etc.). Well, to make the repository aware of the
entity it should take care of, we have to define the model property:

```php
/**
* The model the repository corresponds to.
*
* @var string
*/
public static $model = 'App\\Models\\Post'; 
```

## Fields

Fields are the main component of the Repository definition. These fields will be exposed through the endpoints the
repository expose. A good practice for the API, is to expose as minimum fields as you can, so your API will be as
private as possible.

Let's define some fields for our Post model:

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
    public static $model = 'App\\Models\\Post';

    /**
     * Resolvable attributes before storing/updating
     * 
     * @param  RestifyRequest  $request
     * @return array
     */
    public function fields(RestifyRequest $request) 
    {
        return [
            Field::new('title'),
            
            Field::new('description'),
        ];
    }
}
```

:::tip
`Field` class has many mutations, validators and interactions you can use, these are
documented [here](/docs/4.0/repository-pattern/field)
:::

## Show request

Now, your `GET` endpoint will expose the `title` and the `description` of the Post. Let's take a look of the json
response of the `api/restify/posts/1` route:

```json
{
    "data": {
        "id": "1",
        "type": "posts",
        "attributes": {
            "title": "Amet ratione est quas quia ut nemo.",
            "description": null
        },
        "meta": {
            "authorizedToShow": true,
            "authorizedToStore": true,
            "authorizedToUpdate": false,
            "authorizedToDelete": false
        }
    }
}
```

Let's explain each piece of the response, and see how we can impact or modify it.

The `id` field by default is the `id` of the response (your table primary key). You can modify this by defining your
own `$id` property into the repository:

```php
    // PostRepository.php
    
    /**
     * Attribute that should be used for displaying the `id` in the json:format
     *
     * @var string
     */
    public static $id = 'uuid';
```

The next piece is the resource type, this is the table name, however you can change that by overriding the `getType`
repository method:

```php
    protected function getType(RestifyRequest $request): ?string
    {
        return 'articles';
    }
```

Then we have the `attributes`, as we already saw, they are defined into the `fields` method.

The last piece would be the `meta`, by default here we have some authorizations over the entity. Authorizations are
computed based on the policy methods, for example the `authorizedToShow` represent the response of the `show` method
from the related policy (PostPolicy in our example).

You can customize the `meta` by creating your own `resolveShowMeta` method:

```php
    // PostRepository.php

    public function resolveShowMeta($request)
    {
        return [
            'is_published' => $this->resource->isPublished(),
        ];
    }
```

:::tip Resource property In the previous example we have used the `$this->resource` call, well, keep in mind, that you
always have access to the current resource in your not static methods of the repository, were the resource is the actual
current model. In the case above, the `$this->resource` represents the `Post` model with the `id=1`, because we're
looking for the route: `/api/restify/posts/1`. A similar way to get the model is the `$this->model()` method.
:::

Well, a lot of methods to modify the serialization partials, however, you are free to customize the entire response at
once by defining:

```php
    // PostRepository.php
    
    public function serializeForShow(RestifyRequest $request): array
    {
        return [
            // Your custom response for the `show` request.
        ];
    }
```

### Custom show

If you want to override the `show` method, you can do it like that:

```php
    public function show(RestifyRequest $request, $repositoryId)
    {
        return response($this->model());
    }
```

## Index request

Since we already understood how the `show` method works, let's take a closer look over the endpoint which returns all
your entities, and how it actually authorizes and serialize them.

Let's take a closer look of what Restify returns for the index route `api/restify/posts`:

```json
{
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 4,
        "path": "http://restify-app.test/api/restify/posts",
        "per_page": 15,
        "to": 15,
        "total": 50
    },
    "links": {
        "first": "http://restify-app.test/api/restify/posts?page=1",
        "last": "http://restify-app.test/api/restify/posts?page=4",
        "prev": null,
        "next": "http://restify-app.test/api/restify/posts?page=2"
    },
    "data": [
        {
            "id": "91ad2f77-e30c-4090-a79c-49417540fdaa",
            "type": "posts",
            "attributes": {
                "title": "Nihil assumenda sit pariatur.",
                "description": null
            },
            "meta": {
                "authorizedToShow": true,
                "authorizedToStore": true,
                "authorizedToUpdate": false,
                "authorizedToDelete": false
            }
        },
        ...
        }
    ]
}
```

Firstly we have the `meta` object, by default this includes pagination information, so your frontend could be adapted
accordingly.

If you want to modify it, you can do so easily in the repository:

```php
public function resolveIndexMainMeta(
    RestifyRequest $request, 
    Illuminate\Support\Collection $items, 
    array $paginationMeta
): ?array
{
    return array_merge(
    $paginationMeta,
    [
        'published_items_count' => $items->filter->isPublished()->count(),
    ]
    )
}
```

In the `resolveIndexMainMeta` you get as arguments - the Restify request, a collection of items (matching the current request) and the original pagination metadata information.

In the previous example, we append the property `published_items_count` which count published posts, so we have this meta:

```json
{
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 4,
        "path": "http://restify-app.test/api/restify/posts",
        "per_page": 15,
        "to": 15,
        "total": 50,
        "published_items_count": 10
    },
...
```

You can return `null` if you don't need meta information.

Next, we get an object called `links`, this one contain navigation links, they could be used in the frontend table component.

You can customize it as well: 

```php
public function resolveIndexLinks(
RestifyRequest $request, 
Illuminate\Support\Collection $items,
array $links): ?array
{
    return $links;
}
```

You can return `null` if you don't need `links` information to be displayed at all.


The next important property is the `data`. Here we have listed items matching the request query, and filtered by the `show` authorization. So in terms of see a model, you should be authorized by the model policy `show` method to do so, if not, it will be filtered out from this response.

The individual item object format is pretty much the same as we have for the [show](#show-request). However, you can specify a custom metadata for these items by using:

```php
public function resolveIndexMeta($request)
{
    return $this->resolveShowMeta($request);
}
```

You're also free to define your own index method:

```php
public function index(RestifyRequest $request)
{
    return response(Post::all());
}
```

### Specific fields

Sometimes, you have very different fields for the `index` request versus `show`. In this situations you can define a specific method for the index fields in your repository, which will return specific fields for that request:

```php
public function fieldsForIndex(RestifyRequest $request): array
{
    return $this->fields($request);
}
```

:::tip Specific fields
Similar with the specific fields for the `index` request, you can define methods to return specific fields for other requests, so we have: `fieldsForIndex`, `fieldsForShow`, `fieldsForStore` and `fieldsForUpdate`.
:::


## Store request

Let's take a closer look at the fields list for the `PostRepository`:

```php 
    public function fields(RestifyRequest $request) 
    {
        return [
            Field::new('title'),
            
            Field::new('description'),
        ];
    }
```

Well, for the `store` request, Restify will use the same fields, and will assign the value from the request matching the attribute name.

:::warning Fillable
Restify will fill your model attributes (defined in the `fields` method) even if they are listed as `$guarded`. 
:::

Let's take an example, here is the payload:

```json
{
    "title": "Beautiful day!",
    "description": "Comming soon..."
}
```

Then we have the request:

```http request
POST: http://restify-app.test/api/restify/posts
```

Restify will store the new post, and will return an `201` (created) status, a `Location` header containing the url to the newly created entity: `/api/restify/posts/1`, and a `data` object with the newly created entity: 

```json
{
    "data": {
        "id": "91ad557d-5780-4e4b-bedc-c35d400d8594",
        "type": "posts",
        "attributes": {
            "title": "Beautiful day!",
            "description": "Comming soon..."
        },
        "meta": {
            "authorizedToShow": true,
            "authorizedToStore": true,
            "authorizedToUpdate": false,
            "authorizedToDelete": false
        }
    }
}
```


## Update request

Taking the same example as we had before, the update will use `title` and the `description` attributes from the request:

```json
{
    "description": "Ready to be published!"
}
```

Endpoint:

```http request
PUT: http://restify-app.test/api/restify/posts/1
```

:::warning Policy
As we saw before, we were denied from the policy for the update operation ( "authorizedToUpdate": false), we have to update the policy `update` method to return `true`.
:::

The Restify response contains the http 200 status, and the following response:

```json
{
    "data": {
        "id": "91ad557d-5780-4e4b-bedc-c35d400d8594",
        "type": "posts",
        "attributes": {
            "title": "Beautiful day!",
            "description": "Ready to be published!"
        },
        "meta": {
            "authorizedToShow": true,
            "authorizedToStore": true,
            "authorizedToUpdate": true,
            "authorizedToDelete": false
        }
    }
}
```

## Delete request

This request is a simple one (don't forget to allow the policy):

```http request
DELETE: http://restify-app.test/api/restify/posts/1
```

If you're allowed to delete the resource, you will get back an `204 No content` response.


-------

## Repository prefix

Restify generates the URI for the repository in the following way:

```php
config('restify.base') . '/' . UserRepository::uriKey() . '/'
```

For example, let's assume we have the `restify.base` equal with: `api/restify`, the default URI generated for the UserRepository is: 

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

:::tip For the rest of the repositories the prefix will stay as it is, the default one.

Keep in mind that this custom prefix, will be used for all the endpoints related to the user repository.
:::

## Repository middleware

Each repository has the middlewares from the config `restify.middleware` out of the box for the CRUD methods. However, you're free to add your own middlewares for a specific repository.

```php
    // PostRepository.php

    public static $middleware = [
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

The Laravel [service container](https://laravel.com/docs/7.x/container) is used to resolve all Laravel Restify
repositories. As a result, you are able to type-hint any dependencies your `Repository` may need in its constructor. The
declared dependencies will automatically be resolved and injected into the repository instance:

:::tip Parent
Don't forget to call the parent `contructor`.
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

## Custom CRUD

Laravel Restify magically made all "CRUD" operations for you. However, sometimes you may want to intercept, or override
the entire logic of a specific action. Let's say your `save` method has to do something else besides action itself. In
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

Laravel Restify has its own "CRUD" routes, however you're able to define your custom routes right from your Repository class:

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
    return response('Done');
}
```

Lets diving into a more "real life" example. Let's take the Post repository we had above:

:::tip Route wrap
The `$wrap` argument is the one who says to your route to be wrapped in the default `middlewares`, `controllers namespace` and `prefix` your routes with the base of the repository (ie `/api/restify/posts/`).
:::

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

At this moment Restify built the new route as a child of the `posts`, so it has the route:

```http request
GET: /api/restify/posts/{id}/kpi
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

As we noticed in the example above, the route is a child of the current repository, however sometimes you may want to
have a separate prefix, which is out of the URI of the current repository. Restify provide you an easy of doing that, by
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

Now the generated route will look like this:

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

By default, each route defined in the `routes` method, will have the namespace `AppRootNamespace\Http\Controllers`. You
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

:::warning Non wrapped route
Clean routes if `$wrap` is false, your routes will have any Route group `$attributes`, that means no prefix,
middleware, or namespace will be applied out of the box, even you defined that as a default argument in the `routes` method. So you should take care of that.
:::

## Force eager loading

However, Laravel Restify [provides eager](/search/) loading based on the query `related` property, you may want to force
eager load a relationship in terms of using it in fields, or whatever else:

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
by using native Laravel validator, so you will have exactly the same experience. The validation `messages` could still
be used as usual.

### Bulk Payload

The payload for a bulk store should contain an array of objects:

```http request
POST: /api/restify/posts/bulk/update
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

After storing an entity, the repository will call the static `bulkStored` method from the repository, so you can
override:

```php
public static function storedBulk(Collection $repositories, $request)
{
    //
}
```

## Update bulk flow

As the store bulk, the update bulk uses DB transaction to perform the action. So you can make sure that even all
entries, even no one where updated.

### Bulk update field validations

```php
->updateBulkRules('required', Rule::in('posts:id'))
```

### Bulk Payload

The payload for a bulk update should contain an array of objects. Each object SHOULD contain an `id` key, based on this,
the Laravel Restify will find the entity:

```http request
POST: /api/restify/posts/bulk/update
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

## Repository boot

You can handle the repository boot, by using the `booted` static method:

````php
/**
     * The "booted" method of the repository.
     *
     * @return void
     */
    protected static function booted()
    {
        static::$wasBooted = true;
    }
````




