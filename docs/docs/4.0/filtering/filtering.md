# Filtering entities

Laravel Restify provides configurable and powerful way of filtering over entities.

## Search

If you want search for some specific fields from a model, you have to define these fields in the `$search` static 
property:

```php
class PostRepository extends Repository
{
    public static $search = ['id', 'title'];
```

Now `posts` are searchable by `id` and `title`, so you could use `search` query param for filtering the index 
request: 

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

There may be situations when the filter you want to apply not necessarily is a database attributes. In your `booted` method you can add more filters for the `$match` where the key represents the field used as query param, and value should be a `Closure` which gets the request and current query `Builder`:

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

Sometimes you may have a large logic into a match `Closure`, and the booted method could become a mess. To prevent this, `Restify` provides a declarative way to define `matchers`. For this purpose you should define a class, and implement the `Binaryk\LaravelRestify\Repositories\Matchable` contract. You can use the following command to generate that:

```shell script
php artisan restify:match ActiveMatch
```

Then you will get the `ActiveMatch` class in `app/Restify/Matchers` folder:

```php
namespace App\Restify\Matchers;

use Binaryk\LaravelRestify\Repositories\Matchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ActiveMatch implements Matchable
{
    public function handle(Request $request, Builder $query)
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

## Sort 
When index query entities, usually we have to sort by specific attributes. 
This requires the `$sort` configuration:

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

## Eager loading - aka withs

When get a repository index or details about a single entity, often we have to get the related entities (we have access to).
This eager loading is configurable by Restify as follow: 

```php
public static $related = ['posts'];
```

This means that we could use `posts` query for eager loading posts:

```http request
GET: /api/restify/users?with=posts
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
