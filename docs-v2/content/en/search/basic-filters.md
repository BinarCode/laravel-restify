---
title: Basic Filters
menuTitle: Basic Filters
category: Search & Filters
position: 10
---

For searching by specific model attributes, define these fields into the `$search` static
property:

```php
class PostRepository extends Repository
{
    public static $search = ['id', 'title'];
```

Now `posts` are searchable by `id` and `title`, so you could use `search` query param for filtering the index request:

```http request
GET: /api/restify/posts?search="Test title"
```

## Match

Matching by specific attributes may be useful if you want an exact matching.

Repository configuration:

```php
class PostRepository extends Repository
{
    public static $match = [
        'id' => RestifySearchable::MATCH_INTEGER
        'title' => RestifySearchable::MATCH_TEXT,
    ];
}
```

As we may notice the match configuration is an associative array, defining the attribute name and type mapping.

Available types:

- text (or `string`)
- bool
- int (or `integer`)
- datetime
- array

When performing the request you may pass the match field and value as query params:

```http request
GET: /api/restify/posts?id=1
```

or by title:

```http request
GET: /api/restify/posts?title="Some title"
```

### Match datetime

The `datetime` filter add behind the scene an `whereDate` query.

```php
class PostRepository extends Repository
{
    public static $match = [
        'published_at' => RestifySearchable::MATCH_DATETIME,
    ];
}
```

Request:

```http request
GET: /api/restify/posts?published_at=2020-12-01
```

### Match null

Match accept `null` as a value, and check add `whereNull` to the query:

```http request
GET: /api/restify/posts?published_at=null
```

### Match array

Match also accept a list of elements in the query param:

```php
class PostRepository extends Repository
{
    public static $match = [
        'id' => RestifySearchable::MATCH_ARRAY
    ];
}
```

Request:

```http request
GET: /api/restify/posts?id=1,2,3
```

This will be converted to:

```php
->whereIn('id', [1, 2, 3])
```

### Match negation

You can negate the column match by simply adding the `-` (minus) sign before the field:

```http request
GET: /api/restify/posts?-id=1,2,3
```

This will return all posts where doesn't have the `id` in the `[1,2,3]` list.

You can apply `-` (negation) for every match:

```http request
GET: /api/restify/posts?-title="Some title"
```

This will return all posts that doesn't contain `Some title` substring.

### Match closure

There may be situations when the filter you want to apply is not necessarily a database attribute. In your `booted`
method you can add more filters for the `$match` where the key represents the field used as query param, and value
should be a `Closure` which gets the request and current query `Builder`:

```php
// UserRepository
protected static function booted()
{
    static::$match['active'] => function ($request, $query) {
        if ($request->boolean('active')) {
           $query->whereNotNull('email_verified_at');
       } else {
           $query->whereNull('email_verified_at');
        }
    }
}
```

So now you can query this:

```http request
GET: /api/restify/users?active=true
```

### Matchable

Sometimes you may have a large logic into a match `Closure`, and the booted method could become a mess. To prevent
this, `Restify` provides a declarative way to define `matchers`. For this purpose you should define a class, and
implement the `Binaryk\LaravelRestify\Repositories\Matchable` contract. You can use the following command to generate
that:

```shell script
php artisan restify:match ActiveMatch
```

Then you will get the `ActiveMatch` class in `app/Restify/Matchers` folder:

```php
namespace App\Restify\Matchers;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ActiveMatch extends MatchFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', $request->boolean('active'));
    }
}
```

The next step is to associate this class with the match key name in your `$match` array:

```php
 public static $match = [
    'active' => ActiveMatch::class,
];
```

### Get available matches

You can use the following request to get all repository matches:

```http request
/api/restify/posts/filters?only=matches
```

## Sort

When index query entities, usually we have to sort by specific attributes. This requires the `$sort` configuration:

```php
class PostRepository extends Repository
{
    public static $sort = ['id'];
```

Performing request requires the sort query param:

Sorting DESC requires a minus (`-`) sign before the attribute name:

 ```http request
GET: /api/restify/posts?sort=-id
```

Sorting ASC:

 ```http request
GET: /api/restify/posts?sort=id
```

or with plus sign before the field:

 ```http request
GET: /api/restify/posts?sort=+id
```

### Sort using BelongsTo

Sometimes you may need to sort by a `belongsTo` relationship. This become a breeze with Restify. Firstly you have to
instruct your sort to use a relationship:

```php
// PostRepository
use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Filters\SortableFilter;

public static function sorts(): array
{
    return [
        'users.name' => SortableFilter::make()
            ->setColumn('users.name')
            ->usingBelongsTo(
                BelongsTo::make('user', 'user', UserRepository::class),
        )
    ];
}
```

Make sure that the column is fully qualified (include the table name).

The request could look like:

```http request
GET: /api/restify/posts?sort=-users.name
```

This will return all posts, sorted descending by users name.

<alert>

Set column optional
As you may notice we have typed twice the `users.name` (on the array key, and as argument in the `setColumn` method). As soon as you use the fully qualified key name, you can avoid the `setColumn` call, since the column will be injected automatically based on the `sorts` key.

</alert>

### Sort using closure

If you have a quick sort method, you can use a closure to sort your data:

```php
// PostRepository
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

public static function sorts(): array
{
    return [
        'users.name' => function(RestifyRequest $request, $query, $direction) {
            // custom sort
        }
    ];
}
```

### Get available sorts

You can use the following request to get sortable attributes for a repository:

```http request
/api/restify/posts/filters?only=sortables
```

<alert>

All filters You can use `/api/restify/posts/filters?only=sortables` request, and
concatenate: `?only=sortables,matches, searchables` to get all of them at once.

</alert>

## Related

When get a repository index or details about a single entity, often we have to get the related entities (we have access to). This eager loading is configurable by Restify as following:

```php
public static $related = ['posts'];
```

This means that we could use `posts` query for eager loading posts:

```http request
GET: /api/restify/users?related=posts
```

## Custom data

You are not limited to add only relations under the `related` array. You can use whatever you want, for instance you can
return a simple model, or a collection. Basically any serializable data could be added there. For example:

```php
public static $related = [
    'foo'
];
```

Then in the `Post` model we can define this method as:

```php
public function foo() {
    return collect([1, 2]);
}
```

## Related field

You can define an eager field to represent and serialize your data:

```php

use Binaryk\LaravelRestify\Fields\BelongsTo;
use Binaryk\LaravelRestify\Fields\MorphToMany;

public static function related(): array
{
    return [
        'user' => BelongsTo::make('user', 'user', UserRepository::class),
        'comments' => MorphToMany::make('comments', 'comments', CommentRepository::class),
    ];
}
```

You have the following relations available: `MorphToMany`, `MorphOne` `BelongsTo`, `HasOne`, `HasMany`, `BelongsToMany`.

This way you can restrict a specific relationship based on a user role:

```php
use Binaryk\LaravelRestify\Fields\BelongsTo;use Illuminate\Http\Request;

public static function related(): array
{
    return [
        'user' => BelongsTo::make('user', 'user', UserRepository::class)->canSee(function(Request $request) {
            return $request->user()->hasRole('owner');
        })
    ];
}
```

### Custom data format

You can use a custom related cast class (aka transformer). You can do so by modifying the `restify.casts.related`
property. The default related cast is `Binaryk\LaravelRestify\Repositories\Casts\RelatedCast`.

The cast class should extends the `Binaryk\LaravelRestify\Repositories\Casts\RepositoryCast` abstract class.

This is the default cast:

```php
    'casts' => [
        /*
        |--------------------------------------------------------------------------
        | Casting the related entities format.
        |--------------------------------------------------------------------------
        |
        */
        'related' => \Binaryk\LaravelRestify\Repositories\Casts\RelatedCast::class,
    ],
```

## Pagination

Laravel Restify has returns `index` items paginates. The default `perPage` is 15.

You can modify that by modifying `$defaultPerPage` property:

```php
class PostRepository extends Repository
{
    public static $defaultPerPage = 30;
}
```

The per page could be changed via query param `perPage`:

```http request
`/api/restify/posts?perPage=30
```


### Get available searchables

You can use the following request to available searchable attributes for a repository:

```http request
/api/restify/posts/filters?only=searchables
```
