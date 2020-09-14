# User Profile

[[toc]]

Laravel Restify expose the user profile via `GET: /api/restify/profile` endpoint.

## Get profile

Get profile:

// GET

```http request
/api/restify/profile
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
/api/restify/profile
```


## User avatar

If your user has an avatar, you can use the Restify endpoints to update the avatar: 

// POST

```http request
api/restify/profile/avatar
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


## Profile via User Repository

You can use the `UserRepository` (if you have one), to resolve the profile and perform the updates over the profile by using the `UserProfile` trait in your repository: 

```php
class UserRepository extends Repository
{
    use Binaryk\LaravelRestify\Repositories\UserProfile;
}
```

Now you can inform whatever the Restify can use this repository to return the profile: 

```php
protected static function booted()
{
    static::$canUseForProfile = true;
}
```

or to update it:

```php
protected static function booted()
{
    static::$canUseForProfileUpdate = true;
}
```

If the `UserRepository` is used to get the user profile, the format of the data will follow the JSON:API format: 

```json
{
  "id": "1"
  "type": "users"
  "attributes": {
    "name": "Eduard Lupacescu"
    "email": "eduard.lupacescu@binarcode.com"
    "password": "" 
  }
}
```

You can specify related entities as for an usual request: 

```http request
GET: api/restify/profile?related=posts
```

So Restify will attach the relationships to the response: 

```json
{
  "id": "1"
  "type": "users"
  "attributes": {
    "name": "Eduard Lupacescu"
    "email": "eduard.lupacescu@binarcode.com"
  }
  "relationships": {
    "posts": array:1 [
      0 => {
        "attributes": {
          "id": 1
          "user_id": "1"

```

If you want to attach some custom / meta profile data, you can do that by overriding the: 


```php
// UserRepository
public static function metaProfile(Request $request): array
{
    return static::$metaProfile;
}
```
