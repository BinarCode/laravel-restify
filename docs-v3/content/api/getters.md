---
title: Getters 
menuTitle: Getters 
category: API 
position: 10
---

## Motivation

Restify provides powerful filters and gets routes with relationships. However, sometimes you might want to get some extra data for your repositories.

Let's say you have a stripe user. This is how you retrieve the stripe user information through a get request:

```php
Route::get('users/stripe-information', UserStripeController::class);

// UserStripeController.php

public function __invoke(Request $request)
{
  ...
}
```

The `classic` approach is good, although it has a few limitations. First, you have to take care of the route `middleware` manually, as the testability for these endpoints should be done separately, which might be hard to maintain. At last, the endpoint is disconnected from the repository, which makes it feel out of context so has a bad readability.

That way, code readability, testability, and maintainability can become hard.

## Invokable Getter Format

The simplest way to define a getter is to use the `invokable` class format.

Here's an example:

```php
namespace App\Restify\Getters;

class StripeInformationGetter
{
    public function __invoke()
    {
        return response()->json([
            'foo' => 'bar',
        ]);
    }
}
```

Then add the getter instance to the repository `getters` method:

```php
...
public function getters(RestifyRequest $request): array
{
    return [
        new StripeInformationGetter,
    ];
}
...
```

Bellow we will see how to define getters in a more advanced way.

## Getter definition

Getters are very similar to actions in this sense. The big difference is that getters only allow GET requests, and should not perform any kind of DB data writing:

The getter is nothing more than a class that extends the `Binaryk\LaravelRestify\Getters\Getter` abstract class.

An example of a getter class:

```php
namespace App\Restify\Getters;

use Binaryk\LaravelRestify\Getters\Getter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;use Illuminate\Support\Collection;

class StripeInformationGetter extends Getter
{
    public static $uriKey = 'stripe-information';
    
    public function handle(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->asStripeUser()
        ]);
    }
}
```

### Register getter

Then add the getter instance to the repository `getters` method:

```php
// UserRepository.php

public function getters(RestifyRequest $request): array
{
    return [
        StripeInformationGetter::new()
    ];
}
```

### Authorize getter

You can authorize certain getters to be active for specific users:

```php
public function getters(RestifyRequest $request): array
{
    return [
        StripeInformationGetter::new()->canSee(function (Request $request) {
            return $request->user()->can('seeStripeInfo'),
        }),
    ];
}
```

### Call getters

To call a getter, you simply access:

```http request
GET: api/restify/posts/getters/stripe-information
```

The `getter` query param value is the `ke-bab` form of the filter class name by default, or a custom `$uriKey` [defined in the getter](#custom-uri-key)


### Handle getter

As soon the getter is called, the handled method will be invoked with the `$request`:

```php
public function handle(Request $request)
{
    //

    return ok();
}
```

## Getter customizations

Getters could be easily customized.

### Custom keys

Since your class names could change along the way, you can define a `$uriKey` property to your getters, so the frontend will use always the same `getter` query when applying a getter:

```php
class StripeInformationGetter extends Getter
{
    public static $uriKey = 'stripe-information';
    //...

};
```

## Getters scope

By default, any getter could be used on [index](#index-getters) as well as on [show](#show-getters). However, you can choose to instruct your getter to be displayed to a specific scope.

## Show getters

Show getters are used when you have to apply them for a single item.

### Show getter definition

The show getter definition differs in how it receives arguments for the `handle` method. 

Restify automatically resolves Eloquent models defined in the route id and passes them to the getter's handle method:

```php
public function handle(Request $request, User $user): JsonResponse
{

}

```

### Show getter registration

To register a show getter, we have to use the `->onlyOnShow()` accessor:

```php
public function getters(RestifyRequest $request)
{
    return [
        StripeInformationGetter::new()->onlyOnShow(),
    ];
}
```

### Show getter call

The post URL should include the key of the model we want Restify to resolve:

```http request
GET: api/restfiy/users/1/getters/stripe-information
```
### List show getters

To get the list of available getters only for a specific model key:

```http request
GET: api/api/restify/posts/1/getters
```

## Index getters

Index getters are used when you have to apply them for many items.

### Index getter definition

The index getter definition differs in how it receives arguments for the `handle` method. 

```php
public function handle(Request $request): JsonResponse
{
    //
}

```

### Index getter registration

To register an index getter, we have to use the `->onlyOnIndex()` accessor:

```php
public function getters(RestifyRequest $request)
{
    return [
        StripeInformationGetter::new()->onlyOnIndex(),
    ];
}
```

### Index getter call

The post URL:

```http request
GET: api/restfiy/posts/getters/stripe-information
```

### List index getters

To get the list of available getters:

```http request
GET: api/api/restify/posts/getters
```
