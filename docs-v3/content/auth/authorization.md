---
title: Authorization
menuTitle: Authorization
category: Auth
position: 1
---

After setting up the Restify configuration and the authentication, the next logical step is to protect your API Repositories against unauthorized users. 

## Request lifecycle

Before diving into details about authorization, it is important for you to understand what is the actual lifecycle of the request. On that account, you can know what to expect and how to debug your app at any point.

### Booting

When you run a request (ie via Postman), it hits the Laravel application. Laravel will load every single Service Provider it has defined into `config/app.php` and [auto discovered ](https://laravel.com/docs/packages#package-discovery) providers as well.

Restify injects the `RestifyApplicationServiceProvider` in your `config/app.php` and it also has an auto discovered provider called `\Binaryk\LaravelRestify\LaravelRestifyServiceProvider`.

- The `LaravelRestifyServiceProvider` is booted first. This will basically push the `RestifyInjector` middleware at the end of the middleware stack. 

- Then, the `RestifyApplicationServiceProvider` is booted. This will define the gate, will load repositories and make the auth routes macro. You now have full control over this provider.

- The `RestifyInjector` will be handled. It will register all the routes.

- On each request, if the requested route is a Restify route, Laravel will handle other middlewares defined in the `restify.php` -> `middleware`. Here is where you should have the `auth:sanctum` middleware to protect your API against unauthenticated users.

## Prerequisites

Before we dive into the details of authorization, we need to make sure that you have a basic understanding of how Laravel's authorization works. If you are not familiar with it, we highly recommend reading the [documentation](https://laravel.com/docs/authorization) before you move forward.

You may also visit the [Authentication/login](/auth/authentication#authorization) section to learn how to login and use the Bearer token.


## View Restify

Since we are now aware of how Restify boots itself, let's see how to guard it.

Let's take a closer look at the package's global gate:

<alert> This gate is only active in a non-local environment. </alert>

```php
// app/Providers/RestifyServiceProvider.php

protected function gate()
{
    Gate::define('viewRestify', function ($user) {
        return in_array($user->email, [
            //
        ]);
    });
}
```

This is the first gate to access the Restify repositories. In a real-life project, you may allow every authenticated user to have access to repositories and just after that, by using policies you can restrict certain specific actions. To do so: 

```php
Gate::define('viewRestify', function ($user) {
    return true;
});
```

If you want to allow unauthenticated users to be authorized to see the restify routes, you can nullify the `$user`:

```php
Gate::define('viewRestify', function ($user = null) {
    return true;
});
```

From this point, it's highly recommended to have a policy for each model exposed via Restify. Otherwise, users may access unauthorized resources, which is not what we want.

## Policies

If you are not aware of what a policy is, we highly recommend reading the [documentation](https://laravel.com/docs/authorization#creating-policies) before you move forward.

You can use the Laravel command for generating a policy. It is greatly recommended to generate a policy using the Restify command because it will scaffold Restify's CRUD authorization methods for you:

```shell script
php artisan restify:policy UserPolicy
```

It will automatically detect the `User` model (the word before `Policy`). However, you can set out the following example: 

```shell script
php artisan restify:policy PostPolicy --model=Post
```

<alert>
It will ultimately be considered that the model lives into the `app/Models` directory.
</alert>

<alert type="warning">
By default, Restify will unauthorize any requests if there isn't a defined policy method associated to the request's endpoint. Or, if you don't have a policy at all, all requests from that repository will be unauthorized.
</alert>

If you already have a policy, here is the Restify default scaffolded one so you can apply these methods on your own:

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function allowRestify(User $user = null): bool
    {
        //
    }

    public function show(User $user, Post $model): bool
    {
        //
    }

    public function store(User $user): bool
    {
        //
    }

    public function storeBulk(User $user): bool
    {
        //
    }

    public function update(User $user, Post $model): bool
    {
        //
    }

    public function updateBulk(User $user, Post $model): bool
    {
        //
    }

    public function delete(User $user, Post $model): bool
    {
        //
    }

    public function deleteBulk(User $user, Post $model): bool
    {
        //
    }

    public function restore(User $user, Post $model): bool
    {
        //
    }

    public function forceDelete(User $user, Post $model): bool
    {
        //
    }
}
```

<alert type="info">
For the examples below, we will consider PostRepository as being a pertinent example.
</alert>

### Allow restify

Just after Restify detects the repository class, it will invoke this method to check if the given user can load this repository in any manner. You can also check if the user is an admin for some specific repositories, such as:

```php
// PostPolicy
/**
 * Determine whether the user can use restify feature for each CRUD operation.
 * So if this is not allowed, all operations will be disabled
 * @param User $user
 * @return mixed
 */
public function allowRestify(User $user)
{
    return $user->isAdmin();
}
```

### Allow show

From here, each policy corresponds to an exposed Restify route.

In addition, the `show` method, corresponds to the following routes:

```http request
POST: /api/restify/posts // it will filter out the entities you don't have access to from the pagination
```

and:

```http request
POST: /api/restify/posts/{id} // it will give a 403 Forbidden status if you don't have access to the resource
```

Definition:
 
```php
/**
 * Determine whether the user can get the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function show(User $user, Post $model)
{
    //
}
```

### Allow store

Determine if a specific user has access to the POST's route in order to create an entity. 

The `store` method, corresponds to the following route:

```http request
POST: /api/restify/posts
```

Definition:

```php
/**
 * Determine whether the user can create models.
 *
 * @param User $user
 * @return mixed
 */
public function store(User $user)
{
    //
}
```
### Allow storeBulk

Determine if the user can store multiple entities at once.

The `storeBulk` method corresponds to the following route:

```http request
POST: api/posts/bulk
```

Definition:

```php
/**
 * Determine whether the user can create multiple models at once.
 *
 * @param User $user
 * @return mixed
 */
public function storeBulk(User $user)
{
    //
}
```

### Allow update

Determine if the user can update a specific model.

The `update` method corresponds to the following routes:

<code-group>

  <code-block label="Full Update" active>
  
  ```http request
  PUT: api/restify/posts/{id}
  ```
  </code-block>

  <code-block label="Partial Update">

  ```http request
  PATCH: api/restify/posts/{id}
  ```
  </code-block>

  <code-block label="File uploads">

  ```http request
  POST: api/restify/posts/{id}
  ```
  </code-block>

</code-group>

Definition:

```php
/**
 * Determine whether the user can update the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function update(User $user, Post $model)
{
    //
}
```

### Allow updateBulk
Determine if the user can update multiple entities at once. When you bulk update, this method will be invoked for each entity you're trying to update. If at least one will return false - none will be updated. The reason behind that is that the bulk update is a DB transaction.

The `updateBulk` method, corresponds to the following route:

```http request
POST: api/restify/posts/bulk/update
```

Definition:
```php
/**
 * Determine whether the user can update bulk the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function updateBulk(User $user = null, Post $model)
{
    return true;
}
```

### Allow delete

The delete endpoint policy.

The `delete` method, corresponds to the following route:

```http request
DELETE: api/restify/posts/{id}
```

Definition:

```php
/**
 * Determine whether the user can delete the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function delete(User $user, Post $model)
{
    //
}
```


### Allow deleteBulk

Determine if the user can delete multiple entities at once. When performing bulk deletion, this method will be invoked for each entity you're trying to delete.

The deleteBulk method corresponds to the following route:

```http request
DELETE: api/restify/posts/bulk/delete
```

Definition:

```php
/**
 * Determine whether the user can delete multiple models at once.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function deleteBulk(User $user, Post $model)
{
    //
}
```


### Allow Attach

<alert type="warning">

Here is where we're talking about pivot tables. Many to many relationships.

</alert>

When attaching a model to another, we should check if the user is also able to do that. For example, attaching posts to a user:

```http request
POST: api/restify/users/{id}/attach/posts
```
```json
{ "posts": [1, 2, 3] }
```

Restify will guess the policy's name by the related entity. For this reason, it will be `attachPost`:

```php
// UserPolicy.php

/**
 * Determine if the post could be attached to the user.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function attachPost(User $user, Post $model)
{
    return $user->is($model->creator()->first());
}
```

The `attachPost` method will be called for each individual post.

### Allow Detach

<alert type="warning">

Here we're talking about pivot tables. Many to many relationships.

</alert>

When detaching a model from another, we should check if the user is also able to do that. For example, detaching posts from a user:

```http request
POST: api/restify/users/{id}/detach/posts
```
```json
{ "posts": [1, 2, 3] }
```

 Restify will guess the policy's name by the related entity. For this reason, it will be `detachPost`:

```php
/**
 * Determine if the post could be attached to the user.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function detachPost(User $user, Post $model)
{
    return $user->is($model->creator()->first());
}
```

The `detachPost` method, will be called for each post in part.

## Register Policy

A common mistake is that sometimes you may define a policy, but you don't attach it to a model in your `app/Providers/AuthServiceProvider.php`. Make sure you have it figured out here.

See [documentation](https://laravel.com/docs/authorization#registering-policies).
