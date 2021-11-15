---
title: Repositories 
menuTitle: Repositories 
category: API 
position: 6
---

The Repository is the core of the Laravel Restify.

## Quick start

For convenience, Restify includes a `restify:repository` Artisan command. This command will create a repository
in `app/Restify` directory that is associated to the `App\Models\Post` model:

```shell script
php artisan restify:repository PostRepository
```

The newly created repository will be placed in the `app/Restify/PostRepository.php` file.

By default, the generation repository command doesn't require any option, however, you can specify `--app` option, to
instruct Restify to generate the migrations, policy and model (in `app/Models`).

## Defining Repositories

The basic repository form looks like this:

```php
namespace App\Restify;

use App\Models\Post;
use App\Restify\Repository;

class PostRepository extends Repository
{
    public static $model = Post::class;
    
    public function fields(RestifyRequest $request)
    {
        return [];
    }
}
```

The `fields` method returns the default set of attributes definitions that should be applied during API requests.

### Model & Repository Discovery Conventions

Restify will discover recursively all classes from the `app\Restify\*` directory, that extend
the `Binaryk\LaravelRestify\Repositories\Repository` class.

If the `$model` property is not defined, Restify will guess the model class by using the prefix of the Repository name,
for example: `UserPostRepository` class has the model `UserPost`.

### Actions handled by the Repository

Having this in place you're basically ready for the CRUD actions over posts. You have available the follow endpoints:

| Verb          | URI                            | Action  |
| :------------- |:----------------------------- | :-------|
| **GET**            | `/api/restify/posts`          | index   |
| **GET**            | `/api/restify/posts/actions`          | index actions  |
| **GET**            | `/api/restify/posts/{post}`   | show    |
| **GET**            | `/api/restify/posts/{post}/actions`          | individual actions  |
| **POST**           | `/api/restify/posts`          | store   |
| **POST**            | `/api/restify/posts/actions?action=actionName`          | perform index actions  |
| **POST**           | `/api/restify/posts/bulk`     | store multiple   |
| **POST**           | `/api/restify/posts/bulk/update`     | update multiple   |
| **PATCH**          | `/api/restify/posts/{post}`   | partial update  | 
| **PUT**            | `/api/restify/posts/{post}`   | full update  |
| **POST**           | `/api/restify/posts/{post}`   | partial of full update including attachments  | 
| **POST**            | `/api/restify/posts/{post}/actions?action=actionName`          | perform index actions  |
| **DELETE**         | `/api/restify/posts/{post}`   | destroy |

<alert> 

Update with files As you can see we provide 3 Verbs for the model update (PUT, PATCH, POST), the reason of that is
because you cannot send files via `PATCH` or `PUT` verbs, so we have `POST`. Where the `PUT` or `PATCH` could be used
for full model update and respectively partial update.

</alert>

## Model name

As we already noticed, each repository basically works as a wrapper over a specific resource. The fancy
naming `resource` is nothing more than a database entity (posts, users etc.). Well, to make the repository aware of the
entity it should take care of, we have to define the model property:

```php
public static $model = 'App\\Models\\Post'; 
```

## Fields

Fields are the main component of the Repository definition. These fields represent model attributes, that will be
exposed through the repository's endpoints. A good practice for the API, is to expose as minimum fields as you can, so
your API will be as private as possible.

In a way, `fields` are similar with the `toArray` method from
the [laravel resource](https://laravel.com/docs/eloquent-resources#concept-overview) concept.

Let's define some fields for our Post model:

```php
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Repositories\Repository;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class PostRepository extends Repository
{
    public function fields(RestifyRequest $request) 
    {
        return [
            Field::make('title'),
            
            Field::make('description'),
        ];
    }
}
```

<alert>

Field class has many mutations, validators and interactions you can use, these are documented [here](/api/fields)

</alert>

## Show request

Now, your `GET` endpoint will expose the `title` and the `description` of the Post. The json response of
the `api/restify/posts/1` route:

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

### ID

```php
// PostRepository.php
    
public static string $id = 'uuid';
```

The next piece is the resource type, this is the table name, however you can change that using `$type` property:

### Type

```php
// PostRepository.php
    
public static string $type = 'articles';
```

Then we have the `attributes`, as we already saw, they are defined into the `fields` method.

### Meta
The last piece would be the `meta`, by default here we have some authorizations over the entity. Authorizations are
computed based on the policy methods, for example the `authorizedToShow` represent the response of the `show` method
from the related policy (PostPolicy in our example).

You can customize the `meta` by creating your own `resolveShowMeta` method:

```php
  // PostRepository.php

  public function resolveShowMeta($request)
  {
      return [
          'is_published' => $this->model()->isPublished(),
      ];
  }
```

<alert>

Keep in mind, that you always have access to the current model in your not static methods of the repository. In the case above, the `
$this->model()` represents the `Post` model with the `id=1`, because we're looking for the route: `/api/restify/posts/1`.

</alert>

As we saw before, there are many ways to partially modify the serialized response for the `show` request, however, you
are free to customize the entire response at once by defining:

```php
// PostRepository.php

public function serializeForShow(RestifyRequest $request): array
{
    return [
        //
    ];
}
```

### Custom show

You can take the full control over the show method:

```php
public function show(RestifyRequest $request, $repositoryId)
{
    return response($this->model());
}
```

## Index request

Since we already understood how the `show` method works, let's take a closer look over the endpoint that returns all
your entities, and how it actually authorizes and serializes them.

This is a standard index `api/restify/posts` response:

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

### Index main meta

Firstly we have the `meta` object, by default this includes pagination information, so your frontend could be adapted
accordingly.

If you want to modify it, you can do so easily in the repository:

```php
public function resolveIndexMainMeta(RestifyRequest $request, Collection $items, array $paginationMeta): ?array
{
    return array_merge($paginationMeta, [
        'published_items_count' => $items->filter->isPublished()->count(),
    ]);
}
```

In the `resolveIndexMainMeta` you get as arguments - the Restify request, a collection of items (matching the current
request) and the original pagination metadata information.

In the previous example, we append the property `published_items_count` which count published posts, so we have this
meta:

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

### Index links

Next, we get an object called `links`, this one contain navigation links, they could be used in the frontend table
component.

You can customize it as well:

```php
public function resolveIndexLinks(RestifyRequest $request, Collection $items, array $links): ?array
{
    return $links;
}
```

You can return `null` if you don't need `links` information to be displayed at all.

The next important property is the `data`. Here we have listed items matching the request query, and filtered by
the `show` authorization policy. So in terms of see a model, you should be authorized by the model policy `show` method to do
so, if not, it will be filtered out from this response.

### Index item meta

The individual item object format is pretty much the same as we have for the [show](#show-request). However, you can
specify a custom metadata for these items by using:

```php
public function resolveIndexMeta($request)
{
    return [
        //...
    ];
}
```

### Custom index

You're also free to define your own index method from scratch:

```php
public function index(RestifyRequest $request)
{
    return response(Post::all());
}
```

### Index fields

By default, attributes used to serialize the index item, are the same from the `fields` method. However, you can define individual fields for the index: 

```php
public function fieldsForIndex(RestifyRequest $request): array
{
    return [
        Field::make('title'),
   ];
}
```

<alert>

Specific fields per request type, could be defined for other requests. For example: `fieldsForIndex`, `fieldsForShow`, `fieldsForStore`
and `fieldsForUpdate`.

</alert>

## Store request

Store, is a `post` request, usually used to create/store entities. Let's take a closer look at the fields list for the `PostRepository`:

```php 
  public function fields(RestifyRequest $request) 
  {
      return [
          Field::make('title'),
          
          Field::make('description'),
      ];
  }
```

Well, for the `store` request, Restify will use the same fields, and will assign the value from the request matching the
attribute name.

<alert type="warning">

Fillable Restify will fill your model attributes (defined in the `fields` method) even if they are listed as `$guarded`.

</alert>

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

Restify will store the new post, and will return an `201` (created) status, a `Location` header containing the url to
the newly created entity: `/api/restify/posts/1`, and a `data` object with the newly created entity:

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

### Store Validation

In a normal Laravel application, you have a store method into a controller, and you have to validate fields using the request: 

```php
$request->validate([
    'description' => 'required',
])
```

To do this in Restify, you have to apply the Field's `storingRules`: 

```php
Field::make('description')->storingRules('required'),
```

So the rules list will be applied for the underlining field.

### Custom store

You can always take the ownership over the store method by overwriting it in the repository: 

```php
// PostRepository.php

public function store(RestifyRequest $request)
{
    //
}
```

<alert type="info">

To note that the validation and authorization is done before the `store` method. So this method is called only if you have access and the fields validation passes.

</alert>

## Update request

Update request is similar with the [store](#store-request). Taking the payload:

```json
{
  "description": "Ready to be published!"
}
```

And the endpoint:

```http request
PUT: http://restify-app.test/api/restify/posts/1
```

<alert type="warning"> 

Policy As we saw before, we were denied from the policy for the update operation ( "authorizedToUpdate":
false), we have to update the policy `update` method to return `true`.

</alert>

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

### Update validation

To validate certain fields we can use the Field's `updatingRules` method: 

```php
Field::make('description')->updatingRules('required'),
```

### Custom update

You can override the update method entirely: 

```php
public function update(RestifyRequest $request, $repositoryId)
{
    //
}
```

Keep in mind that, this method is called only when the policy authorization and fields validation passes.

## Delete request

This request is a simple one (don't forget to allow the policy):

```http request
DELETE: http://restify-app.test/api/restify/posts/1
```

If you're allowed to delete the resource, you will get back an `204 No content` response.

### Custom destroy

You can override the `destory` method: 

```php
public function destroy(RestifyRequest $request, $repositoryId)
{
    //
}
```

### Soft deletion

Now, Restify uses the `->delete()` eloquent method to delete the model. So if you're using soft deletion, it will soft delete it.

## Store bulk flow

The bulk store means that you can create many entries at once, for example if you have a list of invoice entries,
usually you have to create those in a single Database Transaction, that's why we have this way to create many entries at
once:

```http request
POST: /api/restify/posts/bulk
```

With the payload:

```json
[
  {
    "title": "Post 1",
    "description": "Description post 1"
  },
  {
    "title": "Post 2",
    "description": "Description post 2"
  }
]
```

### Bulk store field validations

Similar with `store` and `update` methods, `bulk` rules has their own field rule definition:

```php
->storeBulkRules('required', function () {}, Rule::in('posts:id'))
```

The validation rules will be merged with the rules provided into the `rules()` method. The validation will be performed
by using native Laravel validator, so you will have exactly the same experience. The validation `messages` could still
be used as usual.

### Unauthorize to bulk store

In the `PostPolicy` you can define a method against the bulk store actions:

```php
/**
 * Determine whether the user can create multiple models at once.
 *
 * @param User $user
 * @return mixed
 */
public function storeBulk(User $user)
{
    return true;
}
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

### Bulk update Payload

The payload for a bulk update should contain an array of objects. Each object should contain an `id` key, based on this,
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

## Force eager loading

However, Laravel Restify [provides eager](/search/) loading based on the query `related` property, you may want to force
eager load a relationship in terms of using it in fields, or whatever else:

```php
// UserRepository.php

public static $withs = ['posts'];
```

<alert type="warning">

`withs` is not a type Laravel uses the `with` property on models, on repositories we use `$withs`, it's not a typo.

</alert>
