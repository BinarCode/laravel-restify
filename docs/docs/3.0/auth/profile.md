# User Profile

[[toc]]

Laravel Restify expose the user profile via `GET: /restify-api/profile` endpoint.

## Get profile

Get profile:

// GET

```http request
/restify-api/profile
```

### Additional information

However, the default user information could be enough in many cases, you are still free to add your own information on the user profile. 

```php
// User.php

public function profile()
{
    return [
        'roles' => [
            'admin',
        ],
    ];
}
```

## Update Profile

Restify will update only `fillable` user attributes present in the request payload: `$request->only($user->getFillable())`.

// PUT

```http request
/restify-api/profile
```


## User avatar

If your user has an avatar, you can use the Restify endpoints to update the avatar: 

// POST

```http request
restify-api/profile/avatar
```

The payload could be a form data, with an image under `avatar` key.

The default path for storing avatar is: `/avatars/{user_key}/`.

You can modify that by modifying property in a `boot` method of any service provider:

```php
Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest::$path
```

Or if you need the request to make the path: 

```php
Binaryk\LaravelRestify\Http\Requests\ProfileAvatarRequest::usingPath(function(Illuminate\Http\Request $request) {
    return 'avatars/' . $request->user()->uuid
})
```

