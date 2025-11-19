---
title: User Profile
menuTitle: Profile
category: Auth
position: 1
---

## Prerequisites  

Make sure you followed the [Authentication](/docs/auth/authentication) guide before, because one common mistake is that people do not add this middleware:

```php
// config/restify.php
'middleware' => [
// ...
    'auth:sanctum',
// ...
]
```

## Get profile

Before retrieving the user's profile, you need to log in and obtain an authentication token. You can refer to the [login documentation](/auth/authentication#login) for details on how to authenticate a user. Make sure to include the `Bearer {$token}` in the `Authorization` header for the subsequent API requests, either using Postman or cURL.

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

This is what we have for a basic profile:

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

Since the profile is nicely set on by using the UserRepository, you can now benefit from the power of the related entities. For example,
if you want to return user roles:

```php
//UserRepository

public static array $related = [
    'roles',
];
```

Also, make sure that the `User` model has this method that returns a relationship from another table. You can do that or you can simply
return an array:

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

In some cases, you might choose not to use the repository for the profile serialization. Afterwards, you should add the
trait `Binaryk\LaravelRestify\Repositories\UserProfile` into your `UserRepository`:

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

Note that when you're not using the repository, the `?include` will not work anymore.

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

In rare cases you may want to utilize the repository only for non admin users. Make sure to serialize
specific fields for the users:

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

Thus, you instruct Restify to only use the repository for users who are admins of your application.

## Update Profile using repository

By default, Restify will validate and fill only the fields presented in your `UserRepository` for updating the user's
profile. Let's get as an example the following repository fields:

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

If we will try to call the `PUT` method to update the profile without data:

```json
{}
```

We will get back a `4xx` validation:

<alert type="warning"> 

Accept header if you test it via Postman (or other HTTP client) and make sure you always pass the `Accept`
header `application/json`. This will instruct Laravel to return you back the json formatted data:

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

Let's say we have to populate the user `name` in the payload:

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

If you [don't use the repository](./#get-profile-using-repository) for the user's profile, Restify will only
update the `fillable` user attributes that are present in the request payload: `$request->only($user->getFillable())`.

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

You cannot upload a file by using PUT or PATCH verbs, so we should use a POST request instead.

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

If you have to customize the path or disk of the storage file, check the [image field](../repository-pattern/field.html#file-fields)
