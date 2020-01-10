# Repository

[[toc]]

## Introduction

The Repository is the main core of the Laravel Restify, included with Laravel provides the easiest way of 
CRUD over your resources than you can imagine. It works along with 
[Laravel API Resource](https://laravel.com/docs/6.x/eloquent-resources), which means you can use all helpers from there right away.

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

| Verb          | URI             | Action  | 
| :------------- |:--------------------------- | :-------|
| GET            | `/restify-api/posts`          | index   |
| GET            | `/restify-api/posts/{post}`   | show    |
| POST           | `/restify-api/posts`          | store   |
| PATCH          | `/restify-api/posts/{post}`   | update  |
| DELETE         | `/restify-api/posts/{post}`   | destroy |

### Fields
When storing or updating a repository Restify will retrieve from the request all attributes defined in the `fillable`
array of the model and will fill all of these fields as they are sent through the request.
If you want to customize some fields before they are filled to the model `attribute`, 
you can interact with fields by defining them in the `fields` method:
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


### Dependency injection

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
the newly created entity in the database. In this case you can easily override each action from the repository [defined here](#actions-handled-by-the-repository):

### store

```php
    /**
     * @param  RestifyRequest  $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function store(Binaryk\LaravelRestify\Http\Requests\RestifyRequest $request)
    {
        // custom storing
        
        return $this->response();
    }
```

### index

```php
    public function index(RestifyRequest $request, Paginator $paginated)
    {
        // Custom response
    }
```

### show

```php
    public function show(RestifyRequest $request, $repositoryId)
    {
        // Custom finding
    }
```

### update

```php
    public function update(RestifyRequest $request, $model)
    {
        // Custom updating
    }
```

### destroy

```php
    public function destroy(RestifyRequest $request, $repositoryId)
    {
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
repositories at once by modifying the `resolveDetails` method of the abstract Repository:

```php
/**
 * Resolve the response for the details
 *
 * @param $request
 * @param $serialized
 * @return array
 */
public function resolveDetails($request, $serialized)
{
    return $serialized;
}
```

Since the repository extends the [Laravel Resource](https://laravel.com/docs/6.x/eloquent-resources) you may 
may conditionally return a field:

```php

```
## Response customization

## Scaffolding repository
