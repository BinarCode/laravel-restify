---
title: Getters 
menuTitle: Getters 
category: API 
position: 11
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

```php [UserRepository.php]
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

#### Accessing the filtered query

For index getters, you can access the filtered query builder via `$request->filteredQuery()`. This query builder already has all filters, search queries, and other query modifiers applied by Restify:

```php
public function handle(Request $request): JsonResponse
{
    // Get the filtered query builder with all applied filters, search, etc.
    $query = $request->filteredQuery();

    // You can further refine the query
    $data = $query->where('status', 'active')->get();

    return response()->json([
        'data' => $data,
    ]);
}
```

This allows you to build upon the existing query without having to manually apply filters again.

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

### MCP Server Integration

When using Laravel Restify with the Model Context Protocol (MCP), getters are automatically exposed as tools to AI agents. You can enhance the AI's understanding of your getters by providing descriptions and validation rules.

#### Getter Description

Provide a clear description of what your getter retrieves by setting the `description` property or method. This helps AI agents understand when and how to use your getter:

```php
class StripeInformationGetter extends Getter
{
    public string $description = 'Retrieve Stripe customer information and subscription status';

    // Or override the method for dynamic descriptions
    public function description(RestifyRequest $request): string
    {
        return 'Retrieve Stripe customer information and subscription status';
    }

    //...
}
```

#### Validation Rules for AI Schema

The `rules()` method is crucial for MCP integration. Restify automatically converts your Laravel validation rules into JSON Schema that AI agents can understand. This allows the AI to validate parameters before executing the getter:

```php
class UserAnalyticsGetter extends Getter
{
    public string $description = 'Get user analytics for a specific date range';

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'metrics' => ['array'],
            'metrics.*' => ['string', 'in:views,clicks,conversions'],
        ];
    }

    public function handle(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate($this->rules());

        // Getter implementation
        return response()->json([
            'data' => $user->analytics($validated),
        ]);
    }
}
```

The AI agent will automatically receive a JSON Schema indicating:
- `start_date`: date string (required, must be before end_date)
- `end_date`: date string (required, must be after start_date)
- `metrics`: array (optional)
- `metrics.*`: string items (must be one of: views, clicks, conversions)

This schema generation works with 60+ Laravel validation rules including: `email`, `url`, `uuid`, `integer`, `min`, `max`, `between`, `before`, `after`, `in`, `array`, and many more.

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

The index getter definition receives only the `$request` in the `handle` method. You can access the filtered query builder using `$request->filteredQuery()`:

```php
public function handle(Request $request): JsonResponse
{
    // Get the filtered query builder with all applied filters, search, etc.
    $query = $request->filteredQuery();

    // You can further refine the query
    $data = $query->where('status', 'active')->get();

    return response()->json([
        'data' => $data,
    ]);
}

```

The filtered query builder contains all repository filters, search queries, and other query modifiers already applied. This allows you to leverage existing filters without re-implementing them.

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
