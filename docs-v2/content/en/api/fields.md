---
title: Fields
menuTitle: Fields
category: API
position: 8
---

Field is basically the model attribute representation. Each Field generally extends
the `Binaryk\LaravelRestify\Fields\Field` class from the Laravel Restify. This class ships a variety of mutators,
interceptors, validators chaining methods you can use for defining your attribute.

To add a field to a repository, we can simply add it to the repository's fields method. Typically, fields may be created
using their static `new` or `make` method. These methods accept the underlying database column as argument:

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

#### `field` helper

<alert>

Instead of using the `Field` class, you can use the `field` helper.
For example: 
```php 
Field::new('email') => field('email')
```

</alert>

## Authorization

Sometimes you may want to hide certain fields from a group of users. You may easily accomplish this by chaining
the `canSee` method onto your field definition. The `canSee` method accepts a `Closure` which should return `true`
or `false`. The `Closure` will receive the incoming `HTTP` request:


<code-group>

  <code-block label="View" active>

   ```php
      public function fields(RestifyRequest $request)
      {
          return [
              field('role_id')->canSee(fn($request) => $request->user()->isAdmin());
      }
  ```

  </code-block>

  <code-block label="Store">

  ```php
      public function fields(RestifyRequest $request)
      {
          return [
              field('role_id')->canStore(fn($request) => $request->user()->isAdmin())
      }
  ```

  </code-block>

  <code-block label="Update">

  ```php
      public function fields(RestifyRequest $request)
      {
          return [
              field('role_id')->canUpdate(fn($request) => $request->user()->isAdmin())
      }
  ```

  </code-block>

</code-group>


# Validation

There is a gold rule saying - catch the exception as soon as possible on its request way. Validations are the first
bridge of your request information, it would be a good start to validate your input. So you don't have to worry about
the payload anymore.

## Attaching rules

Validation rules could be adding by chaining the `rules` method to
attach [validation rules](https://laravel.com/docs/validation#available-validation-rules)
to the field:

```php
Field::new('email')->rules('required'),
```

Of course, if you are leveraging Laravel's support
for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), you may attach those to resources
as well:

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

If you would like to define rules that only apply when a resource is being storing, you may use the `storingRules`
method:

```php
Field::new('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

## Update Rules

Likewise, if you would like to define rules that only apply when a resource is being updated, you may use
the `updatingRules` method.

```php
Field::new('email')->updatingRules('required', 'email');
```

# Interceptors

However, the default storing process is automatically, sometimes you may want to take the control over it. That's a
breeze with Restify, since Field expose few useful chained helpers for that.

## Fill callback

There are two steps before the value from the request is attached to model attribute. Firstly it is get from the
application request, and go to the `fillCallback` and secondly, the value is transforming by the `storeCallback`
or `updateCallback`:

You may intercept each of those with closures.

```php
Field::new('title')
    ->fillCallback(function (RestifyRequest $request, $model, $attribute) {
        $model->{$attribute} = strtoupper($request->get('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.

## Store callback

Another handy interceptor is the `storeCallback`, this is the step immediately before attaching the value from the
request to the model attribute:

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

## Value Callback

Usually, there is necessary to store a field as `Auth::id()`. This field will be automatically populated by Restify if
you specify the `value` value for it:

```php
Field::new('user_id')->value(Auth::id());
```

or using a closure:

```php
Field::new('user_id')->hidden()->value(function(RestifyRequest $request, $model, $attribute) {
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

However, you can populate the field value when the entity is stored, by using `value`:

```php
Field::new('token')->value(Str::random(32))->hidden();
```

## Default value

If you have a field which has `null` value into the database, however, you want to return a fallback default value for
the frontend:

```php
Field::new('description')->default('N/A');
```

So now, for fields which don't have a description into the database, it will return `N/A`.

## After store

You can handle the after field store callback:

```php
Field::new('title')->afterStore(function($value) {
    dump($value);
})
```

## After update

You can handle the after field is updated callback:

```php
Field::new('title')->afterUpdate(function($value, $oldValue) {
    dump($value, $oldValue);
})
```

# Variations

## File fields

To illustrate the behavior of Restify file upload fields, let's assume our application's users can upload "avatar
photos" to their account. So, our users database table will have an `avatar` column. This column will contain the path
to the profile on disk, or, when using a cloud storage provider such as Amazon S3, the profile photo's path within its "
bucket".

### Defining the field

Next, let's attach the file field to our `UserRepository`. In this example, we will create the field and instruct it to
store the underlying file on the `public` disk. This disk name should correspond to a disk name in your `filesystems`
configuration file:

```php
use Binaryk\LaravelRestify\Fields\File;

public function fields(RestifyRequest $request)
{
    return [
        File::make('avatar')->disk('public')
    ];
}
```

### How Files Are Stored

When a file is uploaded using this field, Restify will use
Laravel's [Filesystem integration](https://laravel.com/docs/filesystem) to store the file on the disk of your choosing
with a randomly generated filename. Once the file is stored, Restify will store the relative path to the file in the
file field's underlying database column.

To illustrate the default behavior of the `File` field, let's take a look at an equivalent route that would store the
file in the same way:

```php
use Illuminate\Http\Request;

Route::post('/avatar', function (Request $request) {
    $path = $request->avatar->store('/', 'public');

    $request->user()->update([
        'avatar' => $path,
    ]);
});
```

If you are using the `public` disk with the `local` driver, you should run the `php artisan storage:link` Artisan
command to create a symbolic link from `public/storage` to `storage/app/public`. To learn more about file storage in
Laravel, check out the [Laravel file storage documentation](https://laravel.com/docs/filesystem).

### Image

The `Image` field behaves exactly like the `File` field; however, it will instruct Restify to only accept mimetypes of
type `image/*` for it:

```php
Image::make('avatar')->storeAs('avatar.jpg')
```

### Storing Metadata

In addition to storing the path to the file within the storage system, you may also instruct Restify to store the
original client filename and its size (in bytes). You may accomplish this using the `storeOriginalName` and `storeSize`
methods. Each of these methods accept the name of the column you would like to store the file information:

```php
Image::make('avatar')
    ->storeOriginalName('avatar_original')
    ->storeSize('avatar_size')
    ->storeAs('avatar.jpg')
```

The image above will store the file, with name `avatar.jpg` in the `avatar` column, the file original name
into `avatar_original` column and file size in bytes under `avatar_size` column (only if these columns are fillable on
your model).

### Pruning & Deletion

File fields are deletable by default, so considering the following field definition:

```php
File::make('avatar')
```

You have a request to delete the avatar of the user with the id 1:

```http request
DELETE: api/restify/users/1/field/avatar
```

You can override this behavior by using the `deletable` method:

```php
File::make('Photo')->disk('public')->deletable(false)
```

So now the field will do not be deletable anymore.

### Customizing File Storage

Previously we learned that, by default, Restify stores the file using the `store` method of
the `Illuminate\Http\UploadedFile` class. However, you may fully customize this behavior based on your application's
needs.

#### Customizing The Name / Path

If you only need to customize the name or path of the stored file on disk, you may use the `path` and `storeAs` methods
of the `File` field:

```php
use Illuminate\Http\Request;

File::make('avatar')
    ->disk('s3')
    ->path($request->user()->id.'-attachments')
    ->storeAs(function (Request $request) {
        return sha1($request->attachment->getClientOriginalName());
    }),
```

#### Customizing The Entire Storage Process

However, if you would like to take **total** control over the file storage logic of a field, you may use the `store`
method. The `store` method accepts a callable which receives the incoming HTTP request and the model instance associated
with the request:

```php
use Illuminate\Http\Request;

File::make('avatar')
    ->store(function (Request $request, $model) {
        return [
            'attachment' => $request->attachment->store('/', 's3'),
            'attachment_name' => $request->attachment->getClientOriginalName(),
            'attachment_size' => $request->attachment->getSize(),
        ];
    }),
```

As you can see in the example above, the `store` callback is returning an array of keys and values. These key / value
pairs are mapped onto your model instance before it is saved to the database, allowing you to update one or many of the
model's database columns after your file is stored.

#### Storeables

Of course, performing all of your file storage logic within a Closure can cause your resource to become bloated. For
that reason, Restify allows you to pass an "Storable" class to the `store` method:

```php
File::make('avatar')->store(AvatarStore::class),
```

The storable class should be a simple PHP class and extends the `Binaryk\LaravelRestify\Repositories\Storable` contract:

```php
<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Repositories\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AvatarStore implements Storable
{
    public function handle(Request $request, Model $model, $attribute): array
    {
        return [
            'avatar' => $request->file('avatar')->storeAs('/', 'avatar.jpg', 'customDisk')
        ];
    }
}
```

### Command

<alert>
You can use the <code>php artisan restify:store AvatarStore</code> command to generate a store file.
</alert>

## BelongsTo

Let's assume each `Post` `belongsTo` a `User`. If we want to return the post owner we can do this from the fields:

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

How cool is that :-)

Sure, having a `BelongsTo` relationship, you have to attach posts to the user when creating the `Post`. This is when the
Restify become very handy. You only have to put the same field attribute, with the `key` (usually `id`) of the related
resource in the payload:

<code-group>

  <code-block label="Request" active>

  ```http request
  POST: http://restify-app.test/api/restify/posts
  ```
  
  </code-block>

  <code-block label="Response">

  ```json
  {
    "description": "Ready to be published!",
    "owner": 1
  }
  ```

  </code-block>

</code-group>

Payload:



### Authorization

You should add the policy method against attaching in the policy. Let's think of it like this, we want to attach a user
to a newly created post, this means we need to add the policy into the `PostPolicy` called `attachUser`:

```php
public function attachUser(User $authenticatedUser, Post $createdPost, User $userToBeAttached) 
{
    return $authenticatedUser->is($userToBeAttached);
}
```

The `attach` policy could be used to the `BelongsTo` field as well, it should return `true` or `false`:

```php
BelongsTo::make('owner', 'user', UserRepository::class)->canAttach(function(
            RestifyRequest $request, 
            PostRepository $repository, 
            User  $userToBeAttached 
) {
            return Auth::user()->is($userToBeAttached);
})
```

As for the [other fields](#authorization), you can easily show / hide the field depending on the user role for example:

```php
BelongsTo::new('owner', 'user', UserRepository::class)->canSee(
           fn($request) => $request->user()->isAdmin()
);
```

## HasOne

The `HasOne` field corresponds to a `hasOne` Eloquent relationship. For example, let's assume a `User`
model `hasOne` `Phone` model. We may add the relationship to our `UserRepository` like so:

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

For the `HasOne` field, there is no way to attach it from Restify, it works the other way around, so you should create
the `Phone` and attach it to the `User` by using the `BelongsTo` field.

## HasMany

The `HasMany` field corresponds to a `hasMany` Eloquent relationship. For example, let's assume a User
model `hasMany` `Post` models. We may add the relationship to our `UserRepository` like so:

```php
// UserRepository@fields()
use Binaryk\LaravelRestify\Fields\HasMany;

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

### Paginate

`HasMany` field returns 15 entries in the `relationships`. This could be customizable from the repository (the
repository being in this case the class of the related resource) class using:

```php
public static $defaultRelatablePerPage = 100;
```

You can also use the query `?relatablePerPage=100`.

### Relatable per page 
<alert type="warning"> 
  
When using `relatablePerPage` query param, it will paginate all relatable entities with that size.

</alert>

## BelongsToMany

The `BelongsToMany` field corresponds to a `belongsToMany` Eloquent relationship. For example, let's assume a `User`
model `belongsToMany` Role models. We may add the relationship to our UserRepository like so:

```php
BelongsToMany::make('roles', 'roles', RoleRepository::class),
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
  "roles": [
    1,
    2
  ],
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
  "roles": [
    1
  ]
}
```

### Custom attach method

If you want to implement attach method for such relationship on your own, Laravel Restify provides you an easy way to do
so. Restify will look for a method which starts with `attach` and concatenated with `Str::studly($relation)` where
the `$relation` is the name of the last segment in the attach URL, `roles` in our case. Let's say you have to attach
roles to user:

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

The first argument is the request, then we get the repository we use for attach, and the parent model (`User` in this
case). Then you are free to have a custom implementation.

If you don't like this kind of `magic` stuff, you can override the `getAttachers` method, and return an associative
array, where the key is the name of the related resource, and the value should be a closure which handle the action:

```php
// UserRepository.php

public static function getAttachers(): array
{
    'roles' => function(RestifyRequest $request, UserRepository $repository, User $user) {
        // custom implementation
    },
}
```

