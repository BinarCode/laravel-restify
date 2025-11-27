---
title: User Profile
menuTitle: Profile
category: Auth
position: 1
---

Laravel Restify provides a convenient profile endpoint that allows authenticated users to retrieve and update their profile information using the same repository system that powers the rest of your API.

## Prerequisites  

Make sure you followed the [Authentication](/auth/authentication) guide first, as you need the authentication middleware configured:

```php [config/restify.php]
'middleware' => [
    // ...
    'auth:sanctum',
    // ...
]
```

## Get profile

The profile endpoint uses your `UserRepository` to serialize the authenticated user's data, giving you full control over what fields are exposed and how relationships are handled.

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

Since the profile uses the UserRepository, you can include related entities using Restify's relationship system. For example, to include user roles:

```php
// UserRepository
use Binaryk\LaravelRestify\Fields\BelongsToMany;

public static function related(): array
{
    return [
        'roles' => BelongsToMany::make('roles', RoleRepository::class),
    ];
}
```

Make sure your `User` model defines the proper Eloquent relationship:

```php [User.php]
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
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
            {
                "id": "1",
                "type": "roles",
                "attributes": {
                    "name": "owner"
                }
            },
            {
                "id": "2", 
                "type": "roles",
                "attributes": {
                    "name": "admin"
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
```

### Repository Control

You can control whether the repository is used for profile serialization by implementing the `canUseForProfile` method:

```php
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class UserRepository extends Repository
{
    public static function canUseForProfile(Request $request): bool
    {
        return true; // Always use repository for profile serialization
    }
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->rules('required'),
            field('email')->rules('required')->storingRules('unique:users'),
        ];
    }
}
```

This method determines whether the repository should be used for profile serialization. Returning `true` enables full repository functionality including field control, validation, and relationships.

## Update profile

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

### File uploads

For file uploads (like user avatars), you must use a POST request instead of PUT or PATCH:

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

### Upload request

<alert type="warning">
You cannot upload a file using PUT or PATCH verbs, so you must use a POST request instead.
</alert>

```http request
POST: /api/restify/profile
```

The payload should be form-data, with an image under the `avatar` key:

```json
{
    "avatar": "binary image in form data request"
}
```

The response will be the updated profile with the new avatar URL:

```json
{
    "id": "7",
    "type": "users", 
    "attributes": {
        "name": "Eduard",
        "email": "interstelar@me.com",
        "avatar": "/storage/avatars/avatar.jpg"
    },
    "meta": {
        "authorizedToShow": true,
        "authorizedToStore": true,
        "authorizedToUpdate": true,
        "authorizedToDelete": true
    }
}
```

If you need to customize the path or disk for the storage file, check the [image field](/api/fields#file-fields) documentation.

## MCP Integration

AI agents can access the user profile using the MCP server's profile tool. When you include the `HasMcpTools` trait in your `UserRepository`, it automatically exposes a `users-profile-tool` that AI agents can use to retrieve the current authenticated user's profile including relationships.

```php
use Binaryk\LaravelRestify\MCP\Concerns\HasMcpTools;

#[Model(User::class)]
class UserRepository extends Repository
{
    use HasMcpTools;
    
    public static function canUseForProfile(Request $request): bool
    {
        return true;
    }
    
    public static function related(): array
    {
        return [
            'roles' => BelongsToMany::make('roles', RoleRepository::class),
        ];
    }
}
```

The AI agent can then use this tool to access profile information:

```json
{
  "method": "tools/call",
  "params": {
    "name": "users-profile-tool",
    "arguments": {
      "include": "roles"
    }
  }
}
```

This provides AI agents with the same authentication and authorization controls as regular API access, ensuring secure profile data access.
