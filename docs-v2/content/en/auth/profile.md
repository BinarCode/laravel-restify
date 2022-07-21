---
title: User Profile
menuTitle: Profile
category: Auth
position: 1
---

## Sanctum middleware

To ensure you can get your profile, you should add the `auth:sanctum` middleware to the restify middleware config:

```php
// config/restify.php

'middleware' => [
    'api',
    'auth:sanctum',
    \Binaryk\LaravelRestify\Http\Middleware\DispatchRestifyStartingEvent::class,
    \Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify::class,
]
```

## Get profile

When retrieving the user profile, by default it is serialized using the `UserRepository`.

```http request
GET: /api/restify/profile
```

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

You can add more `fields` in your `UserRepository` if you want to display.

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

Since the profile is resolved using the UserRepository, you can benefit from the power of related entities. For example,
if you want to return user roles:

```php
//UserRepository

public static array $related = [
    'roles',
];
```

And make sure the `User` model, has this method, which returns a relationship from another table, or you can simply
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

Let's get the profile now, using the `roles` relationship:

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

In some cases, you might choose to not use the repository for the profile serialization. In such cases you should add the
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

In this case, the profile will return the model directly:

### Relations
<alert type="warning"> 

Note that when you're not using the repository, the `?include` will do not work anymore.

</alert>

```http request
/api/restify/profile
```

And you will get:

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

In rare cases you may want to utilize the repository only for non admin users for example, to ensure you serialize
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

This way you instruct Restify to only use the repository for users who are admins of you application.

## Update Profile using repository

By default, Restify will validate, and fill only fields presented in your `UserRepository` for updating the user
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

We will get back `4xx` validation:

<alert type="warning"> 

Accept header If you test it via Postman (or other HTTP client), make sure you always pass the `Accept`
header `application/json`. This will instruct Laravel to return you back json formatted data:

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

So we have to populate the user `name` in the payload:

```json
{
    "name": "Eduard Lupacescu"
}
```

Since the payload is valid now, Restify will update the user profile (name in our case):

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

If you [don't use the repository](./#get-profile-using-repository) for the user profile, Restify will update
only `fillable` user attributes present in the request payload: `$request->only($user->getFillable())`.

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

To prepare your users for avatars, you can add the `avatar` column in your users table:

```php
// Migration
public function up()
{
    Schema::table('users', function( Blueprint $t) {
        $t->string('avatar')->nullable();
    });
}
```

Not you should specify in the user repository that user has avatar file:

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

Now you can use the Restify profile update, and give the avatar as an image.

### Post request

<alert type="warning">

You cannot upload file using PUT or PATCH verbs, so we should use POST request.

</alert>

```http request
POST: /api/restify/profile
```

The payload should be a form-data, with an image under `avatar` key:

```json
{
    "avatar": "binary image in form data request"
}
```

If you have to customize path or disk of the storage file, check the [image field](../repository-pattern/field.html#file-fields)
