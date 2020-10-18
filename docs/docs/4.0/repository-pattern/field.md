# Field

Field is basically the model attribute representation. Each Field generally extends the `Binaryk\LaravelRestify\Fields\Field` class from the Laravel Restify. 
This class ships a variety of mutators, interceptors, validators chaining methods you can use for defining your attribute.

To add a field to a repository, we can simply add it to the repository's fields method. 
Typically, fields may be created using their static `new` or `make` method. These methods accept the underlying database column as argument: 

```php

use Illuminate\Support\Facades\Hash;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

public function fields(RestifyRequest $request)
{
    return [
        Field::new('email')->rules('required')->storingRules('unique:users')->messages([
            'required' => 'This field is required.',
        ]),
        Field::new('password')->storeCallback(function (RestifyRequest $request, $model, $attribute) {
            $model->password = Hash::make($request->input($attribute));
        })->rules('required')->storingRules('confirmed'),
    ];
}
```

:::tip `field` helper
Instead of using the `Field` class, you can use the `field` helper. 
For example: `Field::new('email')` => `field('email')`
:::

# Validation

There is a gold rule saying - catch the exception as soon as possible on its request way. 
Validations are the first bridge of your request information, it would be a good start to validate 
your input. So you don't have to worry about the payload anymore.

## Attaching rules

Validation rules could be adding by chaining the `rules` method to attach [validation rules](https://laravel.com/docs/validation#available-validation-rules)
to the field: 

```php
Field::new('email')->rules('required'),
```

Of course, if you are leveraging Laravel's support for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), 
you may attach those to resources as well:

```php
Field::new('email')->rules('required', new CustomRule),
```

Additionally, you may use [custom Closure rules](https://laravel.com/docs/validation#using-closures) 
to validate your resource fields:

```php
Field::new('email')->rules('required', function($attribute, $value, $fail) {
    if (strtolower($value) !== $value) {
        return $fail('The '.$attribute.' field must be lowercase.');
    }
}),
```

## Storing Rules 

If you would like to define rules that only apply when a resource is being storing, you may use the `storingRules` method:

```php
Field::new('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

## Update Rules

Likewise, if you would like to define rules that only apply when a resource is being updated, you may use the `updatingRules` method.

```php
Field::new('email')->updatingRules('required', 'email');
```


# Interceptors
However, the default storing process is automatically, sometimes you may want to take the control over it. 
That's a breeze with Restify, since Field expose few useful chained helpers for that.

## Fill callback

There are two steps before the value from the request is attached to model attribute. 
Firstly it is get from the application request, and go to the `fillCallback` and secondly, 
the value is transforming by the `storeCallback` or `updateCallback`:

You may intercept each of those with closures.

```php
Field::new('title')
    ->fillCallback(function (RestifyRequest $request, $model, $attribute) {
        $model->{$attribute} = strtoupper($request->get('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.


## Store callback

Another handy interceptor is the `storeCallback`, this is the step immediately before attaching the value from the request to the model attribute:

This interceptor may be useful for modifying the value passed through the `$request`.

```php
Field::new('password')->storeCallback(function (RestifyRequest $request) {
    return Hash::new($request->input('password'));
});
```

## Update callback

```php
Field::new('password')->updateCallback(function (RestifyRequest $request) { 
    return Hash::new($request->input('password'));
});
```
## Index Callback

Sometimes you may want to transform some attribute from the database right before it is returned to the frontend.

Transform the value for the index request:

```php
Field::new('password')->indexCallback(function ($value) {
    return Hash::new($value);
});
```
## Show callback

Transform the value for the show request:

```php
Field::new('password')->showRequest(function ($value) {
    return Hash::new($value);
});
```

## Append Callback

Usually, there is necessary to store a field as `Auth::id()`. This field will be automatically populated by Restify if you specify the `append` value for it:

```php
Field::new('user_id')->append(Auth::id());
```

or using a closure:

```php
Field::new('user_id')->hidden()->append(function(RestifyRequest $request, $model, $attribute) {
    return $request->user()->id;
});
```

## Field label

Field label, so you can replace a field attribute spelling when it is returned to the frontend:

```
Field::new('created_at')->label('sent_at')
```

Of course if you want to populate this value from a frontend request, you can use the label as a payload key.

## Hidden field
Field can be setup as hidden:
```php
Field::new('token')->hidden(); // this will not be visible 
```

However, you can populate the field value when the entity is stored, by using `append`:

```php
Field::new('token')->append(Str::random(32))->hidden();
```

# Variations

Bellow we have a list of fields used for the related resources.

## BelongsTo

Let's assume each post belongs to a user. If we want to return the post owner we can do this from the fields:

```php
    // PostRepository
    public function fields(RestifyRequest $request)
    {
        return [
            Field::new('title'),

            Field::new('description'),

            BelongsTo::make('owner', 'user', UserRepository::class),
        ];
    }
```

Now look, the response of the `api/restify/posts/1` will have this format:

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
                    "email": "jacobi.ferne@gmail.com",
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

How cool is that :-)

Sure, having a BelongsTo relationship, you have to attach posts to the user. This is when the Restify become very handy. You only have to put the same attribute field with the id of the related resource in the payload:

```http request
POST: http://restify-app.test/api/restify/posts
```

Payload:

```json
{
    "description": "Ready to be published!",
    "owner": 1
}
```

You should add the policy method against attaching in the policy. Let's think of it like this, we want to attach a user to a newly created post, this means we need to add the policy into the PostPolicy with the name `attachUser`

```php
    public function attachUser(User $authenticatedUser, Post $createdPost, User $userToBeAttached) 
    {
        return $authenticatedUser->is($userToBeAttached);
    }
```

The `attach` policy could be used to the `BelongsTo` field as well, it should return true or false:

```php
BelongsTo::make('owner', 'user', UserRepository::class)
->canAttach(function(RestifyRequest $request, PostRepository $repository, User  $userToBeAttached) {
return Auth::user()->is($userToBeAttached);
})
```

## HasOne

The `HasOne` field corresponds to a `hasOne` Eloquent relationship. For example, let's assume a `User` model `hasOne` `Phone` model. We may add the relationship to our `UserRepository` like so:

```php
// UserRepository
 public function fields(RestifyRequest $request)
    {
        return [
            Field::new('name'),

            HasOne::new('phone', 'phone', PhoneRepository::class),
        ];
    }
```

## HasMany

The HasMany relationship simply will return a list of related entities.


```php
    // UserRepository -> fields()
    HasMany::make('posts', 'posts', PostRepository::class),
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


## BelongsToMany

The `BelongsToMany` field corresponds to a `belongsToMany` Eloquent relationship. For example, let's assume a User model belongsToMany Role models. We may add the relationship to our UserRepository like so:

```php
    BelongsToMany::make('roles', 'roles', RoleRepository::class),
```

### Pivot fields

If your `belongsToMany` relationship interacts with additional "pivot" attributes that are stored on the intermediate table of the `many-to-many` relationship, you may also attach those to your `BelongsToMany` Restify Field. Once these fields are attached to the relationship field, and the relationship has been defined on both sides, they will be displayed on the request.

For example, let's assume our User model `belongsToMany` Role models. On our `user_role` intermediate table, let's imagine we have a `policy` field that contains some simple text about the relationship. We can attach this pivot field to the `BelongsToMany` field using the fields method:

```php
BelongsToMany::make('roles', 'roles', RoleRepository::class)->withPivot(
    Field::make('policy')
),
```

And you also have to define this in the `User` model: 

```php
public function roles()
{
   return $this->belongsToMany(Role::class, 'user_role')->withPivot('policy');
}
```

### Attach related

Once you have defined the `BelongsToMany` field, you can now attach Role to a User like so:

```http request
POST: api/restify/users/1/attach/roles
```

Payload:

```json
{
    "roles": [1, 2],
    "policy": "Some message."
}
```

### Detach related

We can also detach a role from the user, it could be done like so:

```http request
POST: api/restify/users/1/detach/roles
```

Using the payload:

```json
{
  "roles": [1]
}
```

### Custom attach method

If you want to implement attach method for such relationship on your own, Laravel Restify provides you an easy way to do so. Restify will look for a method which starts with `attach` and concatenated with `Str::studly($relation)` where the `$relation` is the name of the last segment in the attach URL, `roles` in our case.  Let's say you have to attach roles to user:

```php
// app/Restify/UserRepository.php

public function attachRoles(RestifyRequest $request, UserRepository $repository, User $user)
{
    $roles = collect($request->get('roles'))->map(fn($role) => Role::findByName($role, 'web'));

    if ($id = $request->get('company_id')) {
        $user->assignCompanyRoles(
            Company::find($id),
            $roles
        );
    }

    return $this->response()->created();
}
```

The first argument is the request, then we get the repository we use for attach, and the parent model (`User` in this case). Then you are free to have a custom implementation.

If you don't like this kind of `magic` stuff, you can override the `getAttachers` method, and return an associative array, where the key is the name of the related resource, and the value should be a closure which handle the action:

```php
// UserRepository.php

public static function getAttachers(): array
{
    'roles' => function(RestifyRequest $request, UserRepository $repository, User $user) {
        // custom implementation
    },
}
```

