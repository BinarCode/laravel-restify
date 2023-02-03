---
title: Fields 
menuTitle: Fields 
category: API 
position: 8
---

A field is basically the model's attribute representation.

## Declaration

Each Field generally extends the `Binaryk\LaravelRestify\Fields\Field` class from the Laravel Restify. This class ships
a fluent API for a variety of mutators, interceptors and validators.

To add a field to a repository, we can simply add it to the repository's fields method. Typically, fields may be created
using their static `new` or `make` method. 

The first argument is always the attribute name and usually matches the database `column`.

```php

use Illuminate\Support\Facades\Hash;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

public function fields(RestifyRequest $request)
{
    return [
        Field::make('name')->required(),
        
        Field::make('email')->required()->storingRules('unique:users')->messages([
            'required' => 'This field is required.',
        ]),
    ];
}
```

### `field` helper

<alert>

Instead of using the `Field` class, you can use the `field` helper. For example:

```php 
field('email')
```

</alert>

### Computed field

The second optional argument is a callback or invokable, and it represents the displayable value of the field either in `show` or `index` requests. 

```php
field('name', fn() => 'John Doe')
```

The field above will always return the `name` value as `John Doe`. The field is still writeable, so you can update or create an entity by using it.

### Readonly field

If you don't want a field to be writeable you can mark it as readonly: 

```php
field('title')->readonly()
```

The `readonly` accepts a request as well as you can use: 

```php
field('title')->readonly(fn($request) => $request->user()->isGuest())
```

### Virtual field

A virtual field, is a field that's [computed](#computed-field) and [readonly](#readonly-field).

```php
field('name', fn() => "$this->first_name $this->last_name")->readonly()
```


## Authorization

The `Field` class provides a few methods in order to authorize certain actions. Each authorization method accepts a `Closure` that
should return `true`
or `false`. The `Closure` will receive the incoming `\Illuminate\Http\Request` request.

### Can see

Sometimes, you may want to hide certain fields from a group of users. You may easily accomplish this by chaining
the `canSee`:

 ```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canSee(fn($request) => $request->user()->isAdmin())
    ];
}
```

### Can store

The can store closure:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canStore(fn($request) => $request->user()->isAdmin())
}
```

### Can update

The can update closure:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canUpdate(fn($request) => $request->user()->isAdmin())
}
```

## Validation

There is a gold rule that's saying - catch the exception as soon as possible on its request way.

Validations are the first bridge of your request information, so it would be a good start to validate your input. In this manner, you
don't have to worry about the payload anymore.

### Attaching rules

Validation rules could be added by chaining the `rules` method to
attach [validation rules](https://laravel.com/docs/validation#available-validation-rules) to the field:

```php
field('email')->rules('required', 'email'),
```

Of course, if you are leveraging Laravel's support
for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), you may attach those to the resources
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

<alert type="success">

Considering the `required` rule is very often used, Restify provides a `required()` validation
helper: `field('email')->required()`

</alert>

These rules will be applied for all the update and store requests.

### Storing Rules

If you would like to define more specific rules that only apply when a resource is being stored, you might want to use
the `storingRules` method:

```php
Field::new('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

Considering the fact that Restify concatenates rules provided by the `rules()` method, the entire validation for a POST request on
this repository will look like this:

```php
$request->validate([
    'email' => ['required', 'email', 'max:255', 'unique:users,email']
]);
```

### Updating Rules

On this wise, if you would like to define rules that only apply when a resource is being updated, you may use
the `updatingRules` method.

```php
Field::new('email')->updatingRules('required', 'email');
```

## Interceptors

Sometimes you might want to take control over certain Field actions. 

That's why the Field class exposes a lot of chained methods you can call to configure it.

### Fill callback

During the `store` and `update` requests, there are two steps before the value from the Request is attached to the model attribute. 

First, it is retrieved from the application request and passed to the `fillCallback`. Then, the value is passed through the `storeCallback` or `updateCallback`:

You may intercept each of those with closures.

Let's start with the `fillCallback`. It accepts a `callable` (an invokable class) or a Closure. The callable will receive the Request, the repository model (an empty one for storing and filled one for updating) and the attribute name:

```php
field('title')->fillCallback(function (RestifyRequest $request, Post $model, $attribute) {
    $model->title = strtoupper($request->input('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.

### Store callback

Another handy interceptor is the `storeCallback`. This is the step that comes immediately before attaching the value from the
request to the model attribute:

This interceptor may be useful for modifying the value passed through the `$request`.

```php
Field::new('password')->storeCallback(function (RestifyRequest $request) {
    return Hash::make($request->input('password'));
});
```

### Update callback

The `updateCallback` works in the same manner. Let's use an invokable this time:

```php
Field::new('password')->updateCallback(new PasswordUpdateInvokable);
```

Where the `PasswordUpdateInvokable` could be an invokable method: 

```php
class PasswordUpdateInvokable 
{
    public function __invoke(Request $request)
    {
        return Hash::make($request->input('password'));
    }
}
```

### Index Callback

Sometimes, you might want to transform an attribute from the database right before it is returned to the frontend.

Transform the value for the following index request:

```php
Field::new('password')->indexCallback(function ($value) {
    return Hash::make($value);
});
```

### Show callback

Transform the value for the following show request:

```php
Field::new('password')->showCallback(function ($value) {
    return Hash::make($value);
});
```

### Resolve callback

Transform the value for both `show` and `index` requests:

```php
Field::new('password')->showCallback(function ($value) {
    return Hash::make($value);
});
```

### Fields actionable

At times, storing attributes might require the stored model before saving it. 

For example, let's say the Post model uses the [media library](https://spatie.be/docs/laravel-medialibrary/v9/introduction), and has the `media` relationship that is a list of Media files:

```php
// PostRepository

public function fields(RestifyRequest $request): array
{
    return [
        field('title'),
        
        field('files', 
            fn () => $this->model()->media()->pluck('file_name')
        )
        ->action(new AttachPostFileRestifyAction),
    ];
}
```

So we have a virtual `files` field (it's not an actual database column) that uses a [computed field](#computed-field) to display the list of Post's files names. The `->action()` calls and accepts an instance of a class that extends `Binaryk\LaravelRestify\Actions\Action`: 

```php
class AttachPostFileRestifyAction extends Action
{
    public function handle(RestifyRequest $request, Post $post): void
    {
        $post->addMediaFromRequest('file')
            ->toMediaCollection();
    }
}
```

The action gets the `$request` and the current `$post` model. Let's say the frontend has to create a post with a file:

```javascript
const data = new FormData;
data.append('file', blobFile);
data.append('title', 'Post title');

axios.post(`api/restify/posts`, data);
```

We were able to create the post and attach a file using media library in a single request. Otherwise, it would have implied creating 2 separate requests (post creation and file attaching).

Actionable fields handle [store](/repositories#store-request), put, [bulk store](/repositories#store-bulk-flow) and bulk update requests.

## Fallbacks

### Default Stored Value

Usually, there is necessary to store a field as `Auth::id()`. This field will be automatically populated by Restify if
you specify the `value` value for it:

```php
Field::new('user_id')->value(Auth::id());
```

or by using a closure:

```php
Field::new('user_id')->hidden()->value(function(RestifyRequest $request, $model, $attribute) {
    return $request->user()->id;
});
```

### Default Displayed Value

If you have a field which has `null` value into the database, you might want to return a fallback default value for
the frontend:

```php
Field::new('description')->default('N/A');
```

Now, for the fields that don't have a description into the database, it will return `N/A`.

<alert type="info">
The default value is ONLY used for the READ, not for WRITE requests.
</alert>

## Customizations

### Field label

Field label, so you can replace a field attribute spelling when it is returned to the frontend:

```
Field::new('created_at')->label('sent_at')
```

If you want to populate this value from a frontend request, you can use the label as a payload key.

### Hidden field

Field can be setup as hidden:

```php
Field::new('token')->hidden(); // this will not be visible 
```

However, you can populate the field value when the entity is stored by using `value`:

```php
Field::new('token')->value(Str::random(32))->hidden();
```

## Hooks

### After store

You can handle the after field store callback:

```php
Field::new('title')->afterStore(function($value) {
    dump($value);
})
```

### After update

You can handle the after field is updated callback:

```php
Field::new('title')->afterUpdate(function($value, $oldValue) {
    dump($value, $oldValue);
})
```

## File fields

To illustrate the behavior of Restify file upload fields, let's assume our application's users can upload "avatar
photos" to their account. Our users' database table will have an `avatar` column. This column will contain the path
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

<alert type="info">

You can use `field('avatar')->file()` instead of `File::make('avatar')` as well.

</alert>

### How Files Are Stored

When a file is uploaded by using this field, Restify will use
Laravel's [Filesystem integration](https://laravel.com/docs/filesystem) to store the file from the disk of your choice
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
methods. Each of these methods accepts the name of the column that you would want to store the file's information in:

```php
Image::make('avatar')
    ->storeOriginalName('avatar_original')
    ->storeSize('avatar_size')
    ->storeAs('avatar.jpg')
```

The image above will store the file with the name `avatar.jpg` in the `avatar` column, the original file name
into `avatar_original` column and file size in bytes under `avatar_size` column (only if these columns are fillable on
your model).

<alert type="info">

You can use `field('avatar')->image()` instead of `Image::make('avatar')` as well.

</alert>

### Pruning & Deletion

File fields are deletable by default, so check out the following field definition:

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

Now, the field will not be deletable anymore.

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

However, if you would like to take **full** control over the file storage logic of a field, you may use the `store`
method. The `store` method accepts a callable which receives the incoming HTTP request and the model's instance associated
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
pairs are mapped onto your model's instance before it is saved to the database, allowing you to update one or many of the
model's database columns after your file is stored.

### Customizing File Display

By default, Restify will display the file's stored path name. However, you may customize this behavior.

#### Displaying temporary url

For disks such as S3, you may instruct Restify to display a temporary URL to the file instead of the stored path name:

```php
  field('path')
      ->file()
      ->path("documents/".Auth::id())
      ->resolveUsingTemporaryUrl()
      ->disk('s3'),

```

The `resolveUsingTemporaryUrl` accepts 3 arguments:


- `$resolveTemporaryUrl` - a boolean to determine if the temporary url should be resolved. Defaults to `true`.

- `$expiration` - A CarbonInterface to determine the time before the URL expires. Defaults to 5 minutes.

- `$options` - An array of options to pass to the `temporaryUrl` method of the `Illuminate\Contracts\Filesystem\Filesystem` implementation. Defaults to an empty array.

#### Displaying full url

For disks such as `public`, you may instruct Restify to display a full URL to the file instead of the stored path name:

```php
  field('path')
      ->file()
      ->path("documents/".Auth::id())
      ->resolveUsingFullUrl()
      ->disk('public'),

```

#### Storeables

Of course, performing all of your file storage logic within a Closure can cause your resource to become bloated. For
that reason, Restify allows you to pass an "Storable" class to the `store` method:

```php
File::make('avatar')->store(AvatarStore::class),
```

The storable class should be a simple PHP class, because it extends the `Binaryk\LaravelRestify\Repositories\Storable` contract:

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
