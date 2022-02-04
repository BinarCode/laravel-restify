---
title: Relations 
menuTitle: Relations 
category: API 
position: 10
---

## Introduction

Eloquent provides a large variety of relationships. You can read about them [here](https://laravel.com/docs/eloquent-relationships).

Restify handles all relationships and give you an expressive way to list resource relationships.

## Definition

The list of relationships should be defined into a repository method called `related`:

```php
public static function related(): array
{
    return [];
}
```

### Eager fields

The `related` method will return an array that should be a key value pair, where the key is the related name the API will request, and value could be an instance of `Binaryk\LaravelRestify\Fields\EagerField` or a relationship name defined in your model.

Each `EagerField` declaration is similar to the `Field` one. The first argument is the `model` relationship name. The second argument is a repository that represents the related entity. 

Say we have a User that has a list of posts, we will define it this way: 

```php
HasMany::make('posts', PostRepository::class),
```

Let's take a look over all relationships Restify provides:

## BelongsTo & MorphOne

The `BelongsTo` and `MorphOne` eager fields works in a similar way. So let's take the `BelongsTo` as an example.

Let's assume each `Post` [belongsTo](https://laravel.com/docs/eloquent-relationships#one-to-many-inverse) a `User`. To return the post's owner we will define it this way:

```php
// PostRepository
public static function related(): array
{
    return [
        'owner' => \Binaryk\LaravelRestify\Fields\BelongsTo::make('user', UserRepository::class),
    ];
}
```

And the model should define the relationship `user`: 

```php
// Post.php

public function user()
{
    return $this->belongsTo(User::class);
}
```

Now the frontend can list post or posts including the relationship: 

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

The `BelongsTo` field allows you to use the search endpoint to [search over a column](/search/basic-filters#repository-search) from the `belongsTo` relationship by simply using the `searchables`: 

```php
BelongsTo::make('user', UserRepository::class)->searchable(['name'])
```

The `searchable` method accepts an array of database columns from the related entity (`users` in our case).


## HasOne

The `HasOne` field corresponds to a `hasOne` Eloquent relationship. 

For example, let's assume a `User` model `hasOne` `Phone` model. We may add the relationship to our `UserRepository` like so:

```php
// UserRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\HasOne::new('phone', PhoneRepository::class),
  ];
}
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

The `HasMany` and `MorphMany` fields corresponds to a `hasMany` and `morphMany` Eloquent relationship. For example, let's assume a User
model `hasMany` `Post` models. We may add the relationship to our `UserRepository` like so:

```php
// UserRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\HasMany::new('posts', PostRepository::class),
  ];
}
```

So you will get back the `posts` relationship:

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

## Load specific columns

Sometimes you might want to load specific columns from the database into the response. For example, if you have a `Post` model with an `id`, `title` and a `description` column, you might want to load only the `title` and the `description` column in the response.

In order to do this, you can use in the request:

```http request
GET /users/1?include=posts[title|description]
```

### Paginate

`HasMany` field returns 15 entries in the `relationships`. This could be customizable from the repository (the
repository being in this case the class of the related resource) class using:

```php
public static $defaultRelatablePerPage = 100;
```


### Relatable per page

You can also use the query `?relatablePerPage=100`.

<alert type="warning"> 

When using `relatablePerPage` query param, it will paginate all relatable entities with that size.

</alert>

## BelongsToMany & MorphToMany

The `BelongsToMany` and `MorphToMany` field corresponds to a `belongsToMany` or `morphToMany` Eloquent relationship. For example, let's assume a `User`
model `belongsToMany` Role models. We may add the relationship to our UserRepository like so:

```php
// CompanyRepository
public static function related(): array
{
  return [
      \Binaryk\LaravelRestify\Fields\BelongsToMany::new('users', UserRepository::class),
  ];
}
```

### Pivot fields

If your `belongsToMany` relationship interacts with additional "pivot" attributes that are stored on the intermediate
table of the `many-to-many` relationship, you may also attach those to your `BelongsToMany` Restify Field. Once these
fields are attached to the relationship field, and the relationship has been defined on both sides, they will be
displayed on the request.

For example, let's assume our `User` model `belongsToMany` Role models. On our `user_role` intermediate table, let's
imagine we have a `policy` field that contains some simple text about the relationship. We can attach this pivot field
to the `BelongsToMany` field using the fields method:

```php
BelongsToMany::new('users', RoleRepository::class)->withPivot(
    Field::new('is_admin')
),
```

And you also have to define this in the `User` model:

```php
public function users()
{
   return $this->belongsToMany(User::class, 'user_company')->withPivot('is_admin');
}
```

Let's try to get now the list of companies with users: 

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

Once you have defined the `BelongsToMany` field, you can now attach User to a Company like so:

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

You have few options to authorize the `attach` endpoint. 

Firstly, you can define the policy method `attachUsers`, the name should start with `attach` and suffix with the `CamelCase` name of the model relationship name: 

```php
// CompanyPolicy.php

public function attachUsers(User $authenticatedUser, Company $company, User $userToBeAttached): bool
{ 
    return $authenticatedUser->isAdmin();
}
```

The policy `attachUsers` method will be called for each individual `userToBeAttached`. Say you attach - [1, 3] ids, this method will be called twice.

Another way to authorize this, is by using the `canAttach` method to the Eager field directly. This method accepts an invokable class instance or a closure:

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->canAttach(function ($request, $pivot) {
                return $request->user()->isAdmin();
            }),
```

### Override attach

You are free to intercept the attach operation entirely and override it using a closure or an invokable: 

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

You have few options to authorize the `detach` endpoint.

Firstly, you can define the policy method `detachUsers`, the name should start with `detach` and suffix with the `CamelCase` name of the model relationship name:

```php
// CompanyPolicy.php

public function detachUsers(User $authenticatedUser, Company $company, User $userToBeDetached): bool
{ 
    return $authenticatedUser->isAdmin();
}
```

The policy `detachUsers` method will be called for each individual `userToBeDetached`. Say you detach - [1, 3] ids, this method will be called twice.

Another way to authorize this, is by using the `canDetach` method to the Eager field directly. This method accepts an `invokable` class instance or a `closure`:

```php
'users' => BelongsToMany::make('users',  UserRepository::class)
            ->canDetach(
                fn($request, $pivot) => $request->user()->can('detach', $pivot)
            ),
```

### Override detach

You are free to intercept the detach method entirely and override it using a closure or an invokable:

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
```
