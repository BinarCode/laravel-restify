---
title: User Profile
menuTitle: Profile
category: Auth
position: 1
---

## Prerequisites  

Make sure you followed the [Authentication](/auth/authentication) guide first, as one common mistake is not adding this middleware:

```php
// config/restify.php
'middleware' => [
// ...
    'auth:sanctum',
// ...
]
```

## Get profile

Before retrieving the user's profile, you need to log in and obtain an authentication token. You can refer to the [login documentation](/auth/authentication#login) for details on how to authenticate a user. Make sure to include `Bearer {$token}` in the `Authorization` header for subsequent API requests, either using Postman or cURL.

When retrieving the user's profile, it is serialized by using the `UserRepository`.

```http request
GET: /api/restify/profile
```

Here's an example of a cURL request for retrieving the user's profile with a random token:

```bash
curl -X GET "http://your-domain.com/api/restify/profile" \
     -H "Accept: application/json" \
     -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
```

Replace `http://your-domain.com` with your actual domain and `eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...` with the authentication token you obtained after logging in.

Here's what a basic profile response looks like:

```json
{
    "id": "7",
    "type": "users",
    "attributes": {
        "name": "Eduard",
        "email": "interstelar@me.com"
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": true,
        "authorizedToUpdate": true,
        "authorizedToDelete": true
    }
}
```

You can add more `fields` in your `UserRepository` if you want to display them.

```php
public function fields(RestifyRequest $request): array
{
    return [
        field('name')->rules('required'),

        field('email')->rules('required')->storingRules('unique:users'),

        field('age')
    ];
}
```

Since the profile is managed using the UserRepository, you can now benefit from the power of related entities. For example, if you want to return user roles:

```php
//UserRepository

public static array $related = [
    'roles',
];
```

Also, make sure the `User` model has this method that returns a relationship from another table, or you can simply return an array:

```php
//User.php

public function roles(): array
{
    // In a real project, here you will get this information from the database.
    return [
        'owner',
        'admin'
    ];
}
```

Now, let's get the profile by using the `roles` relationship:

```http request
GET: /api/restify/profile?include=roles
```

The result will look like this:

```json
{
    "id": "7",
    "type": "users",
    "attributes": {
        "name": "Eduard",
        "email": "interstelar@me.com"
    },
    "relationships": {
        "roles": [
            "owner",
            "admin"
        ]
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": true,
        "authorizedToUpdate": true,
        "authorizedToDelete": true
    }
}
```

### Without repository

In some cases, you might choose not to use the repository for profile serialization. To do this, you should add the `Binaryk\LaravelRestify\Repositories\UserProfile` trait to your `UserRepository`:

```php
// UserProfile

use Binaryk\LaravelRestify\Repositories\UserProfile;

class UserRepository extends Repository
{
    use UserProfile;

    public static $model = 'App\\Models\\User';
    
    //...
}
```

The profile will return the model directly:

### Relations
<alert type="warning">
Note that when you're not using the repository, the `?include` parameter will not work.
</alert>

```http request
/api/restify/profile
```

You will get:

```json
{
    "data": {
        "id": 7,
        "name": "Eduard",
        "email": "interstelar@me.com",
        "email_verified_at": null,
        "created_at": "2020-12-24T08:49:30.000000Z",
        "updated_at": "2020-12-24T08:52:37.000000Z"
    }
}
```

### Conditionally use repository

In rare cases, you may want to use the repository only for non-admin users. Make sure to serialize specific fields for users:

```php
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\UserProfile;
use Illuminate\Http\Request;

class UserRepository extends Repository
{
    use UserProfile;

    public static $model = 'App\\Models\\User';

    public static function canUseForProfile(Request $request)
    {
        return $request->user()->isAdmin();
    }
    
    public function fields(RestifyRequest $request)
    {
        return [
            field('name')->rules('required'),

            field('email')->rules('required')
                ->storingRules('unique:users')->messages([
                    'required' => 'This field is required.',
                ]),
        ];
    }
}
```

This instructs Restify to use the repository only for users who are admins of your application.

## Update profile using repository

By default, Restify will validate and fill only the fields defined in your `UserRepository` when updating the user's profile. Let's use the following repository fields as an example:

```php
// UserRepository

public function fields(RestifyRequest $request)
{
    return [
        field('name')->rules('required'),

        field('email')->storingRules('required', 'unique:users')->messages([
                'required' => 'This field is required.',
            ]),
    ];
}
```

If we try to call the `PUT` method to update the profile without data:

```json
{}
```

We will get back a `4xx` validation error:

<alert type="warning">
When testing via Postman (or other HTTP client), make sure you always pass the `Accept` header `application/json`. This will instruct Laravel to return JSON-formatted data.
</alert>

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": [
            "The name field is required."
        ]
    }
}
```

Let's say we need to include the user `name` in the payload:

```json
{
    "name": "Eduard Lupacescu"
}
```

Since the payload is valid now, Restify will update the user's profile (a name, in our case):

```json
{
    "id": "7",
    "type": "users",
    "attributes": {
        "name": "Eduard Lupacescu",
        "email": "interstelar@me.com"
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": true,
        "authorizedToUpdate": true,
        "authorizedToDelete": true
    }
}
```

### Update without repository

If you [don't use the repository](#without-repository) for the user's profile, Restify will only update the `fillable` user attributes that are present in the request payload: `$request->only($user->getFillable())`.

```http request
PUT: /api/restify/profile
```

Payload:

````json
{
    "name": "Eduard Lupacescu"
}
````

The response will be the updated user:

```json
{
    "data": {
        "id": 7,
        "name": "Eduard",
        "email": "interstelar@me.com",
        "email_verified_at": null,
        "created_at": "2020-12-24T08:49:30.000000Z",
        "updated_at": "2020-12-24T09:34:48.000000Z"
    }
}
```

## User avatar

To prepare your users for avatars, you can add the `avatar` column in your users' table:

```php
// Migration
public function up()
{
    Schema::table('users', function( Blueprint $t) {
        $t->string('avatar')->nullable();
    });
}
```

Now, you should specify in the user repository that the user has an avatar file:

```php
use Binaryk\LaravelRestify\Fields\Image;

public function fields(RestifyRequest $request)
{
    return [
        field('name')->rules('required'),

        field('avatar')->image()->storeAs('avatar.jpg')
    ];
}
```

You can use the Restify's profile update and give the avatar as an image.

### Post request

<alert type="warning">
You cannot upload a file using PUT or PATCH verbs, so you should use a POST request instead.
</alert>

```http request
POST: /api/restify/profile
```

The payload should be a form-data, with an image under the `avatar` key:

```json
{
    "avatar": "binary image in form data request"
}
```

If you need to customize the path or disk for the storage file, check the [image field](/api/fields#file-fields) documentation.
