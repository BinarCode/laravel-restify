# User Profile

[[toc]]

To ensure you can get your profile, you should add the `Authenticate` middleware to your restify, this can be easily
done by using the `Binaryk\LaravelRestify\Http\Middleware\RestifySanctumAuthenticate::class` into
your `restify.middleware` [configuration file](../quickstart.html#configurations);

Laravel Restify expose the user profile via `GET: /api/restify/profile` endpoint.

## Get profile using repository

When retrieving the user profile, by default it is serialized using the `UserRepository` if there is once (Restify will
find the repository based on the `User` model).

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
public function fields(RestifyRequest $request)
{
    return [
        Field::make('name')->rules('required'),

        Field::make('email')->rules('required')
            ->storingRules('unique:users')->messages([
                'required' => 'This field is required.',
            ]),

        Field::make('age')
    ];
}
```

Since the profile is resolved using the UserRepository, you can benefit from the power of related entities. For example,
if you want to return user roles:

```php
//UserRepository

public static $related = [
    'roles',
];
```

And make sure the `User` model, has this method, which returns a relationship from another table, or you can simply
return an array:

```php
//User.php

public function roles()
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
GET: /api/restify/profile?related=roles
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

In some cases, you may choose to not use the repository for the profile serialization. In such cases you should add the
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

:::warning Relations 
Note that when you're not using the repository, the `?related` will do not work anymore.
:::

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
            Field::make('name')->rules('required'),

            Field::make('email')->rules('required')
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
        Field::make('name')->rules('required'),

        Field::make('email')->storingRules('required', 'unique:users')->messages([
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

:::warning 
Accept header If you test it via Postman (or other HTTP client), make sure you always pass the `Accept`
header `application/json`. This will instruct Laravel to return you back json formatted data:
:::

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
        Field::make('name')->rules('required'),

        Image::make('avatar')->storeAs('avatar.jpg')
    ];
}
```

Now you can use the Restify profile update, and give the avatar as an image.

:::warning Post request 

You cannot upload file using PUT or PATCH verbs, so we should use POST request.
:::

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

### Avatar without repository

If you don't use the repository for updating the user profile, Restify provides a separate endpoint for updating the avatar.

```http request
POST: api/restify/profile/avatar
```

The default path for storing avatar is: `/avatars/{user_key}/`, and it uses by default the `public` disk.

You can modify that by modifying property in a `boot` method of any service provider:

```php
Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest::$path = 'users';
Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest::$disk = 's3';
```

Or if you need the request to make the path:

```php
Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest::usingPath(function(Illuminate\Http\Request $request) {
    return 'avatars/' . $request->user()->uuid
})
```
