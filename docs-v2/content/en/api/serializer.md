---
title: Serializer
menuTitle: Serializer
category: API
position: 12
---

## Introduction

The API response format must stay consistent throughout the application. Ideally, it would be good to follow a standard as
the [JSON:API](https://jsonapi.org/format/) so your frontend app could align with the API.

Restify provides a convenient way to quickly return a response in a consistent format.


## rest

```php
return rest(Company::first())
    ->related('users')
    ->sortDesc('id');
```

The `rest` helper accepts a list of models and returns a `\Binaryk\LaravelRestify\Repositories\Serializer` instance, so you can call its fluent API.

The `Serializer` will look for the repository associated with your models. If there is a repository associated with your Company (ie CompanyRepository), Serializer will use that repository to serialize your models accordingly:

```json
{
  "data": {
    "id": "1",
    "type": "companies",
    "attributes": {
      "name": "BinarCode"
    },
    "relationships": {
      "users": [
        {
          "id": "1",
          "type": "users",
          "attributes": {
            "name": "Eduard",
            "email": "eduard.lupacescu@binarcode.com"
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

In case there isn't a repository associated with your models, the response will simply be a data object with models.

The `rest` helper accepts a model as well as a list (collection) of models, and it'll serialize the response accordingly: 

```php
rest(Post::all())
    ->related('user')
    ->sortDesc('id')
    ->perPage(20)
```

## data

```php
data(User::first(), 200)
```

This helper simply wraps the provided data into an object with a `data` key:

```json
{
  "data": {
    "id": 1,
    "name": "User name",
    "email": "kshlerin.hertha@example.com"
  }
}
```

### ok

```php
ok('All good!')
```

`ok` helper accepts an optional message as argument, so you can return a successful response with a custom message.

```json
{
  "message": "All good!"
}
```
