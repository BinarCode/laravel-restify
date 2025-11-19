---
title: Relations 
menuTitle: Relations 
category: API 
position: 10
---

## Introduction

Eloquent provides a large variety of relationships. You can read about them [here](https://laravel.com/docs/eloquent-relationships).

Restify handles all relationships and gives you an expressive way to list resource relationships.

## Definition

The list of relationships should be defined into a repository method called `related`:

```php
public static function related(): array
{
    return [];
}
```

### Eager fields

The `related` method will return an array that should be a key-value pair, where the key is the related name that the API will request, and the value could be an instance of `Binaryk\LaravelRestify\Fields\EagerField` or a relationship name defined in your model.

Each `EagerField` declaration is similar to the `Field` one. The first argument is the `model` relationship name. The second argument is a repository that represents the related entity. 

Let's say we have a User that has a list of posts. We will define it this way: 

```php
HasMany::make('posts', PostRepository::class),
```
or: 
```php
HasMany::make('posts'),
```

<alert type="info">
Restify 7+ will guess the serialization repository using the key, so you don't necessarily have to specify it:

</alert>

### Related Declaration

Let's see how can we inform a repository about its relationships:

```php
// CompanyRepository
public static function related(): array
{
    return [
        'usersRelationship' => HasMany::make('users', UserRepository::class),
        
        HasMany::make('posts'),
        
        'extraData' => fn() => ['location' => 'Romania'],
        
        'extraMeta' => new Invokable(),
        
        'country',
    ];
}
```

Above we can see a few types of relationships declarations that Restify provides. Let's explain them. 

#### Long definition

```php
'usersRelationship' => HasMany::make('users', UserRepository::class),
```

This means that there is a relationship of the `hasMany` type declared in the Company model. The Eloquent relationship name is `users` (see the first argument of the HasMany field): 

```php
// app/Models/Company.php
public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(User::class);
}
```

The key `usersRelationship` represents the query param the API exposes to load the list of users: 

```http request
GET: api/companies?related=usersRelationship
```

The `UserRepository` represents the repository class that serializes the users list.

#### Short definition

```php
HasMany::make('posts'),
```

Usually the key (query param) and the actual Eloquent relationship names are the same, so Restify provides a shorter version of defining the relationship.

In this case the name of the query param will be the same as the relationship name - `posts`. The name of the repository `PostRepository` will be resolved based on the same key and $uriKey of the repository. 

The request will look like this: 

```http request
GET: api/companies?related=posts
```

#### Callables

```php
'extraData' => fn() => ['location' => 'Romania'],

'extraMeta' => new Invokable()
```
Restify allows you to resolve specific data using callable functions or invokable (classes with a single public __invoke method). You can return any kind of data from these callables. It'll be serialized accordingly. The query param in this case should match the key:

```http request
GET: api/companies?related=extraData,extraMeta
```

#### Forwarding

```php
'country',
```

If you simply define a key in the `related`, Restify will forward your request to the associated model. Your model could return anything, as it might be an Eloquent relationship or any primary data.


Let's take a look over all the relationships that Restify provides:

### Frontend request

In order to get the related resources, you need to send a `GET` request to:

```http request
GET `/api/restify/users?include=posts`
```

Sometimes, you might want to load specific columns from the database into the response. For example, if you have a `Post` model with an `id`, `title`, and a `description` column, you might want to load only the `title` and the `description` column in the response.

In order to do this, you can use the following request:

```http request
GET /users/1?include=posts[title|description]
```

### Nested relationships

Let's assume you have the `CompanyRepository`: 

```php
// CompanyRepository
public static function related(): array
{
    return [
        HasMany::make('users'),
    ];
}
```

In the UserRepository you have a relationship to a list of user posts and roles:

```php
// UserRepository
public static function related(): array
{
    return [
        HasMany::make('posts'),
        MorphToMany::make('roles'),
    ];
}
```

In `PostRepository` you might have a list of comments for each post: 

```php
// PostRepository
public static function related(): array
{
    return [
        HasMany::make('comments'),
    ];
}
```

In order to get the company's users with their posts and roles, you can follow the [laravel syntax for eager loading](https://laravel.com/docs/master/eloquent-relationships#nested-eager-loading) into the request query: 

```http request
GET: /api/restify/companies?include=users.posts,users.roles
```

This request will return a list like this: 

```json
{
  "data": {
    "id": "91c2bdd0-bf6f-4717-b1c4-a6131843ba56",
    "type": "companies",
    "attributes": {
      "name": "Binar Code"
    },
    "relationships": {
      "users": [{
        "id": "3",
        "type": "users",
        "attributes": {
          "name": "Eduard"
        },
        "relationships": {
          "posts": [{
            "id": "1",
            "type": "posts",
            "attributes": {
              "title": "Post title"
            }
          }],
          "roles": [{
            "id": "1",
            "type": "roles",
            "attributes": {
              "name": "admin"
            }
          }]
        }
      }]
    }
  }
}
```

You can also specify and load the `comments` of the `posts`: 

```http request
GET: /api/restify/companies?include=users.posts.comments,users.roles
```

Or specify the exact columns that you want to load for each nested layer: 

```http request
GET: /api/restify/companies?include=users[name].posts[id|title].comments[comment],users.roles[name]
```

<alert type="info">
Getting specific columns will make your requests more performant.

</alert>

### Meta information

Starting with Restify 7+, meta information for related (in index requests) will not be displayed. For more details read the [repository meta](/api/repositories#index-item-meta).

## BelongsTo & MorphOne

The `BelongsTo` and `MorphOne` eager fields work in a similar way, so let's take the `BelongsTo` as an example.

Let's assume each `Post` [belongsTo](https://laravel.com/docs/eloquent-relationships#one-to-many-inverse) a `User`. To return the post's owner, we will define it like this:

```php
// PostRepository
public static function related(): array
{
    return [
        'owner' => \Binaryk\LaravelRestify\Fields\BelongsTo::make('user', UserRepository::class),
    ];
}
```

The model should define the relationship `user`: 

```php
// Post.php

public function user()
{
    return $this->belongsTo(User::class);
}
```

Now the frontend can list a post or posts including the following relationship: 

```http request
GET: api/restify/posts/1?include=owner
```

```json
{
  "data": {
    "id": "91c2bdd0-bf6f-4717-b1c4-a6131843ba56",
    "type": "posts",
    "attributes": {
      "title": "Culpa qui accusamus eaque sint.",
      "description": "Id illo et quidem nobis reiciendis molestiae."
    },
    "relationships": {
      "owner": {
        "id": "3",
        "type": "users",
        "attributes": {
          "name": "Laborum vel esse dolorem amet consequatur.",
          "email": "jacobi.ferne@gmail.com"
        },
        "meta": {
          "authorizedToShow": true,
          "authorizedToStore": true,
          "authorizedToUpdate": false,
          "authorizedToDelete": false
        }
      }
    },
    "meta": {
      "authorizedToShow": true,
      "authorizedToStore": true,
      "authorizedToUpdate": true,
      "authorizedToDelete": true
    }
  }
}
```

### Searchable belongs to

The `BelongsTo` field allows you to use the search endpoint to [search over a column](/search/basic-filters#repository-search) from the `belongsTo` relationship by simply using the `searchables` call: 

```php
BelongsTo::make('user')->searchable('name', 'email')
```

The `searchable` method accepts multiple database attributes from the related entity (`users` in our case).

Therefore, if we get the following search request, it'll also search into the related user's name and email: 

```http request
GET: api/restify/companies?related=user&search="John"
```

You can check if a relation is searchable using:

```php
$field = BelongsTo::make('user')->searchable('name');
$isSearchable = $field->isSearchable(); // true
$attributes = $field->getSearchables(); // ['name']
```

#### Custom Search Callbacks

For advanced search scenarios, you can provide a custom callback to completely control the search behavior:

```php
BelongsTo::make('user')->searchable(function ($query, $request, $value, $field, $repository) {
    return $query->whereHas('user', function ($q) use ($value) {
        $q->where('name', 'ilike', "%{$value}%")
          ->orWhere('email', 'ilike', "%{$value}%")
          ->orWhere('phone', 'like', "%{$value}%");
    });
})
```

The callback receives the following parameters:
- `$query` - The main query builder instance
- `$request` - The current RestifyRequest instance
- `$value` - The search value from the request
- `$field` - The BelongsTo field instance
- `$repository` - The current repository instance

This approach provides maximum flexibility for complex search requirements while maintaining the same API interface.

## HasOne

The `HasOne` field corresponds to a `hasOne` Eloquent relationship. 

For example, let's assume a `User` model `hasOne` `Phone` model. We may add the relationship to our `UserRepository` like so:

```php
// UserRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\HasOne::make('phone', PhoneRepository::class),
  ];
}
```

### Sortable HasOne Relations

`HasOne` relations can be made sortable:

```php
HasOne::make('phone')->sortable('number')
```

This allows sorting by the related model's attributes:

```http request
GET: api/restify/users?sort=phone.number
```

The json response structure will be the same as previously:

```json
{
  "data": {
    "id": "1",
    "type": "users",
    "attributes": {
      "name": "Et maxime voluptatem cumque accusamus sit."
    },
    "relationships": {
      "phone": {
        "id": "2",
        "type": "phones",
        "attributes": {
          "phone": "+40 766 444 22"
        },
        "meta": {
          "authorizedToShow": false,
          "authorizedToStore": true,
          "authorizedToUpdate": false,
          "authorizedToDelete": false
        }
      },
      ...
```

## HasMany & MorphMany

The `HasMany` and `MorphMany` fields correspond to a `hasMany` and `morphMany` Eloquent relationship. For example, let's assume a User
model `hasMany` `Post` models. We may add the relationship to our `UserRepository` as shown:

```php
// UserRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\HasMany::make('posts', PostRepository::class),
  ];
}
```

In addition, you will get back the `posts` relationship:

```json
{
  "data": {
    "id": "1",
    "type": "users",
    "attributes": {
      "name": "Et maxime voluptatem cumque accusamus sit."
    },
    "relationships": {
      "posts": [
        {
          "id": "91c2bdd0-ccf6-49ec-9ae9-8bae1d39c100",
          "type": "posts",
          "attributes": {
            "title": "Rem suscipit tempora ullam accusantium in rerum.",
            "description": "Vero nostrum quasi velit molestiae animi neque."
          },
          "meta": {
            "authorizedToShow": true,
            "authorizedToStore": true,
            "authorizedToUpdate": true,
            "authorizedToDelete": true
          }
        }
      ]
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

### Paginate

`HasMany` field returns 15 entries in the `relationships`. This could be customizable from the repository (the
repository being in this case the class of the related resource) class by using:

```php
public static int $defaultRelatablePerPage = 100;
```


### Relatable per page

You can also use the query `?relatablePerPage=100`.

```http request
GET: api/restify/users?related=posts&relatablePerPage=100
```

<alert type="warning"> 

When using `relatablePerPage` query param, it will paginate all the relatable entities with that size.

</alert>

## BelongsToMany & MorphToMany

The `BelongsToMany` and `MorphToMany` field corresponds to a `belongsToMany` or `morphToMany` Eloquent relationship. For example, let's assume a `User`
model `belongsToMany` Role models. We may add the relationship to our UserRepository like this:

```php
// CompanyRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\BelongsToMany::make('users', UserRepository::class),
  ];
}
```

### Pivot fields

If your `belongsToMany` relationship interacts with additional "pivot" attributes that are stored on the intermediate
table of the `many-to-many` relationship, you may also attach those to your `BelongsToMany` Restify Field. Once these
fields are attached to the relationship field and the relationship has been defined on both sides, they will be
displayed on the request.

For example, let's assume our `User` model `belongsToMany` Role models. On our `user_role` intermediate table, let's
imagine we have a `policy` field that contains a simple text about the relationship. We can attach this pivot field
to the `BelongsToMany` field by using the fields method:

```php
BelongsToMany::make('users', RoleRepository::class)->withPivot(
    field('is_admin')
),
```

You might also need to define this in the `User` model:

```php
public function users()
{
   return $this->belongsToMany(User::class, 'user_company')->withPivot('is_admin');
}
```

Now, let's try to get the list of companies with users: 

```http request
GET: /api/restify/company/1?include=users
```

```json
{
  "data": {
    "id": "1",
    "type": "companies",
    "attributes": {
      "name": "ut"
    },
    "relationships": {
      "users": [
        {
          "id": "1",
          "type": "users",
          "attributes": {
            "name": "Linnea Rowe Sr.",
            "email": "tledner@example.com",
          },
          "meta": {
            "authorizedToShow": true,
            "authorizedToStore": true,
            "authorizedToUpdate": true,
            "authorizedToDelete": true
          },
          "pivots": {
            "is_admin": true
          }
        }
      ]
    },
    "meta": {
      "authorizedToShow": true,
      "authorizedToStore": true,
      "authorizedToUpdate": true,
      "authorizedToDelete": true
    }
  }
}
```

### Attach related

Once you have defined the `BelongsToMany` field, you can now attach User to a Company just like this:

```http request
POST: api/restify/companies/1/attach/users
```

Payload:

```json
{
  "users": [1, 2],
  "is_admin": true
}
```

### Authorize attach

You have a few options to authorize the `attach` endpoint. 

First, you can define the policy method `attachUsers`. The name should start with `attach` and suffix with the `CamelCase` name of the model's relationship name: 

```php
// CompanyPolicy.php

public function attachUsers(User $authenticatedUser, Company $company, User $userToBeAttached): bool
{ 
    return $authenticatedUser->isAdmin();
}
```

The policy `attachUsers` method will be called for each individual `userToBeAttached`. However, if you attach - [1, 3] ids, this method will be called twice.

Another way to authorize this is by using the `canAttach` method to the Eager field directly. This method accepts an invokable class instance or a closure:

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->canAttach(function ($request, $pivot) {
                return $request->user()->isAdmin();
            }),
```

### Override attach

You are free to intercept the attach operation entirely and override it by using a closure or an invokable: 

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->attachCallback(function ($request, $repository, $company) {
                $company->users()->attach($request->input('users'));
            }),
```

Or using an invokable :

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->attachCallback(new AttachCompanyUsers),
```

and then define the class:

```php
use Illuminate\Http\Request;

class AttachCompanyUsers
{
    public function __invoke(Request $request, CompanyRepository $repository, Company $company): void
    {
        $company->users()->attach($request->input('users'));
    }
}
```


### Sync related

You can also `sync` your `BelongsToMany` field. Say you have to sync permissions to a role. You can do it like this:

```http request
POST: api/restify/roles/1/sync/permissions
```

Payload:

```json
{
  "permissions": [1, 2]
}
```

Under the hood this will call the `sync` method on the `BelongsToMany` relationship: 

```php
// $role of the id 1

$role->permissions()->sync($request->input('permissions'));
```

### Authorize sync

You can define a policy method `syncPermissions`. The name should start with `sync` and suffix with the plural `CamelCase` name of the model's relationship name:

```php
// RolePolicy.php

public function syncPermissions(User $authenticatedUser, Company $company, Collection $keys): bool
{ 
    // $keys are the primary keys of the related model (permissions in our case) Restify is trying to `sync`
}
```

### Detach related

As soon we declared the `BelongsToMany` relationship, Restify automatically registers the `detach` endpoint:

```http request
POST: api/restify/companies/1/detach/users
```

Using the payload:

```json
{
  "users": [1]
}
```

### Authorize detach

You have a few options to authorize the `detach` endpoint.

Primarily, you can define the policy method `detachUsers`, as the name should start with `detach` and suffix with the `CamelCase` name of the model relationship name:

```php
// CompanyPolicy.php

public function detachUsers(User $authenticatedUser, Company $company, User $userToBeDetached): bool
{ 
    return $authenticatedUser->isAdmin();
}
```

The policy `detachUsers` method will be called for each individual `userToBeDetached`. If you detach - [1, 3] ids, this method will be called twice.

Another way to authorize this is by using the `canDetach` method to the Eager field directly. This method accepts an `invokable` class instance or a `closure`:

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->canDetach(
                fn($request, $pivot) => $request->user()->can('detach', $pivot)
            ),
```

### Override detach

You are free to intercept the detach method entirely and override it by using a closure or an invokable:

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->detachCallback(function ($request, $repository, $company) {
                $company->users()->detach($request->input('users'));
            }),
```

Or using an invokable :

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->detachCallback(new DetachCompanyUsers),
```

and then define the class:

```php
use Illuminate\Http\Request;

class DetachCompanyUsers
{
    public function __invoke(Request $request, CompanyRepository $repository, Company $company): void
    {
        $company->users()->detach($request->input('users'));
    }
}

## Validation for Attach Operations

You can add custom validation for attach operations:

```php
BelongsToMany::make('users', UserRepository::class)
    ->validationCallback(function ($request, $pivot) {
        return [
            'users.*' => 'exists:users,id',
            'is_admin' => 'boolean'
        ];
    })
    ->unique() // Prevents duplicate attachments
```

The `validationCallback` receives the request and pivot data, and should return validation rules. The `unique()` method prevents duplicate attachments automatically.

## Column Selection in Relations

You can specify which columns to load for relations using Laravel's column selection syntax:

### Basic Column Selection

```http request
GET: /api/restify/users?include=posts[id,title,created_at]
```

### Nested Column Selection

```http request
GET: /api/restify/companies?include=users[id,name].posts[title].comments[comment]
```

### Mixed Column Selection

```php
// In your repository
HasMany::make('posts')->columns(['id', 'title', 'published_at'])
```

## Advanced Sorting

### Sorting by Related Fields

Both `BelongsTo` and `HasOne` relations support sorting:

```php
// BelongsTo sorting
BelongsTo::make('user')->sortable('name')

// HasOne sorting  
HasOne::make('profile')->sortable('bio')
```

### JSON Attribute Sorting

Relations can sort by JSON attributes:

```php
BelongsTo::make('user')->sortable('preferences->theme')
```

### Custom Sort Logic

You can define custom sorting logic:

```php
use Binaryk\\LaravelRestify\\Filters\\SortableFilter;

SortableFilter::make()
    ->usingClosure(function ($query, $direction) {
        return $query->orderBy('custom_logic', $direction);
    })
```

## Morph Relations

Laravel Restify supports all morph relationship types:

### MorphOne

```php
// CommentRepository
public static function related(): array
{
    return [
        MorphOne::make('commentable', PostRepository::class),
    ];
}
```

### MorphMany

```php
// PostRepository  
public static function related(): array
{
    return [
        MorphMany::make('comments', CommentRepository::class),
    ];
}
```

### MorphToMany

```php
// PostRepository
public static function related(): array
{
    return [
        MorphToMany::make('tags', TagRepository::class)->withPivot('created_at'),
    ];
}
```

## Relationship Authorization

### Repository-Level Authorization

Relations inherit authorization from their target repositories. You can customize this:

```php
HasMany::make('posts')->canEnableRelationship(function ($request) {
    return $request->user()->can('view-posts');
})
```

### Policy-Based Authorization

Define policy methods for relation operations:

```php
// In your Policy class
public function viewPosts(User $user, Company $company): bool
{
    return $user->can('view', $company);
}

public function attachUsers(User $user, Company $company, User $userToAttach): bool  
{
    return $user->isAdmin();
}

public function detachUsers(User $user, Company $company, User $userToDetach): bool
{
    return $user->isAdmin(); 
}

public function syncPermissions(User $user, Role $role, Collection $permissionIds): bool
{
    return $user->can('manage-permissions');
}
```

## Performance Optimizations

### Eager Loading Prevention

Relations automatically prevent circular references and deep nesting to avoid performance issues.

### Pagination Control

Control relation pagination globally:

```php
// In your Repository
public static int $defaultRelatablePerPage = 50;
```

Or per request:

```http request
GET: /api/restify/users?include=posts&relatablePerPage=25
```

### Selective Column Loading

Always specify only needed columns:

```http request
GET: /api/restify/users?include=posts[id,title]&fields[users]=id,name
```

## Debugging Relations

### Relation State

Check relation loading state:

```php
$related = Related::make('posts', $field);
$isEager = $related->isEager(); // boolean
$relation = $related->getRelation(); // string
```

### Query Analysis

Relations support query state tracking:

```php
$relatedQuery = RelatedQuery::fromToken('posts[id,title]');
$columns = $relatedQuery->columns(); // ['id', 'title']  
$isSerialized = $relatedQuery->isSerialized(); // boolean
```
