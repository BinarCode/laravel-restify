---
title: Authorization
menuTitle: Authorization
category: Auth
position: 2
---

After setting up the Restify configuration, and the authentication. The next logical step is to protect your API Repositories against unauthorized users. 

## Request lifecycle

Before diving in details about authorization, it's important for you to understand what is the actual lifecycle of the request. So you can know what to expect, and how to debug your app at any point.

### Booting

When you run a request (ie via Postman), it hits the Laravel application. Laravel will load every single Service Provider it has defined into `config/app.php` and [auto discovered ](https://laravel.com/docs/packages#package-discovery) providers as well.

Restify injects the `RestifyApplicationServiceProvider`, it is injected in your `config/app.php` and it also has an auto discovered provider called `LaravelRestify\LaravelRestifyServiceProvider`.

- The `LaravelRestifyServiceProvider` is booted firstly, this will push the `RestifyInjector` middleware at the end of the middleware stack. 

- Then `RestifyApplicationServiceProvider` is booted, this will define the gate, will load repositories and make the auth routes macro. You have the full control over this provider.

- The `RestifyInjector` will be handled. It will register `RestifyCustomRoutesProvider` which load all the custom routes (defined in the `routes` static method in repositories) and will check if this request is a restify one - in which case it will register a new service provider `RestifyServiceProvider`

- If the `RestifyServiceProvider` was registered, it will load all the other CRUD routes Restify provides and will try to bind a custom exception handler defined in the `restify.php` configuration.

- If the request route is a Restify route, Laravel will handle other middlewares defined in the `restify.php` -> `middleware`.


## View Restify

Since we are now aware of how Restify boot itself, let's see how to guard it.

Let's take a closer look to the package global gate:

```php
// app/Providers/RestifyServiceProvider.php
/**
 * Register the Restify gate.
 *
 * This gate determines who can access Restify in non-local environments.
 *
 * @return void
 */
protected function gate()
{
    Gate::define('viewRestify', function ($user) {
        return in_array($user->email, [
            //
        ]);
    });
}
```

This is the first gate to access the Restify repositories. In a real life project, you may allow every authenticated user to have access to repositories, and just after that, using policies you can restrict specific actions. To do so: 

```php
    Gate::define('viewRestify', function ($user) {
        return true;
    });
```

If you want to allow unauthenticated users to be authorized to see restify routes, you can nullify the `$user`:

```php
    Gate::define('viewRestify', function ($user = null) {
        return true;
    });
```

From this point, it's highly recommended having a policy for each model have exposed via Restify. Otherwise, users may access unauthorized resources, which is not what we want.

## Policies

If you are not aware of what a policy is, I highly recommend reading the [documentation](https://laravel.com/docs/authorization#creating-policies) before you move forward.

Restify uses CRUD classic naming to authorize specific actions.

However, you can use the Laravel command for generating a policy, it's recommended to generate a policy using Restify command, because it will scaffold Restify CRUD authorization methods for you:

```shell script
php artisan restify:policy UserPolicy
```

It will automatically detect the `User` model (the word before `Policy`). However, you can specify the model: 

```shell script
php artisan restify:policy SuperUserPolicy --model=User
```

<alert>
It will consider that the model lives into the `app/Models` directory.
</alert>

If you already have a policy, here is the Restify default scaffolded one, so you can take methods on your own:

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can use restify feature for each CRUD operation.
     * So if this is not allowed, all operations will be disabled
     * @param User $user
     * @return mixed
     */
    public function allowRestify(User $user = null)
    {
        //
    }

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

    /**
     * Determine whether the user can update bulk the model.
     *
     * @param User $user
     * @param Post $model
     * @return mixed
     */
    public function updateBulk(User $user, Post $model)
    {
        //
    }

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

    /**
     * Determine whether the user can restore the model.
     *
     * @param User $user
     * @param Post $model
     * @return mixed
     */
    public function restore(User $user, Post $model)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param User $user
     * @param Post $model
     * @return mixed
     */
    public function forceDelete(User $user, Post $model)
    {
        //
    }
}
```

<alert type="warning">
For the examples bellow, we will consider `PostRepository` as being an example.
</alert>

### Allow restify

Just after Restify detects the repository class, it will invoke this method, to check if the given user can load this repository at all. You can check if the user is admin for some specific repositories, for example:

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

From here, bellow, each policy corresponds to an exposed Restify route.

The `show` method, correspond to the routes:

```http request
POST: /api/restify/posts // it will filter out from the pagination the entities you don't have access to
```

and:

```http request
POST: /api/restify/posts/{id} // it will give 403 Forbidden status if you don't have access to the resource
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

Determine if a specific user has access to the POST route for creation an entity. 

The `store` method, correspond to the route:

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

The `storeBulk` method, correspond to the route:

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

The `update` method, correspond to the routes:

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
Determine if the user can update multiple entities at once. When you bulk update, this method will be invoked for each entity you're trying to update, and if at least one will return false, no one will be updated, this is because the bulk update is a DB transaction.

The `updateBulk` method, correspond to the route:

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

The `delete` method, correspond to the route:

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

### Allow Attach

<alert type="warning">

Here we're talking about pivot tables. Many to many relationships.

</alert>

When attaching a model to another, we should check if the user is able to do that. For example attaching posts to a user:

```http request
POST: api/restify/users/{id}/attach/posts
```
```json
{ "posts": [1, 2, 3] }
```

In this case, Restify will guess the policy name, by the related entity, in this case it will be `attachPost`:

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

The `attachPost` method, will be called for each post in part.

### Allow Detach

<alert type="warning">

Here we're talking about pivot tables. Many to many relationships.

</alert>

When detaching a model from another, we should check if the user is able to do that. For example detaching posts from a user:

```http request
POST: api/restify/users/{id}/detach/posts
```
```json
{ "posts": [1, 2, 3] }
```

In this case, Restify will guess the policy name, by the related entity, in this case it will be `detachPost`:

```php
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

The `detachPost` method, will be called for each post in part.

## Register Policy

A common mistake, is that sometimes you may define a policy, but you don't attach it to a model in your `app/Providers/AuthServiceProvider.php`. Make sure you have it defined there.

See [documentation](https://laravel.com/docs/authorization#registering-policies).
