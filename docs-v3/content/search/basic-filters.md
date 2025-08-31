---
title: Filters
menuTitle: Filters
category: Search & Filters
position: 11
---

Restify provides few powerful ways to filter and search your data.

## Global search

Restify provides a global endpoint that searches over all repositories searchable fields. 

To define which repository fields are searchable, you may assign an array of database columns in the `search` property of your repository class. This includes id column by default, but you may override it to your needs:

```php
class PostRepository extends Repository
{
    public static array $search = ['id', 'title'];
```

The endpoint to search is:

```http request
GET: /api/restify/search?search="Test title"
```

It will search over all repositories that are authorized (has `allowRestify` policy on true).

### Disabling global search

There are 2 ways to disable the global search:  for a repository, either return false from the `allowRestify` model policy method or

<list :items="[
'return false from the `allowRestify` model policy method',
'mark the `$globallySearchable` static property false on repository',
]"></list>

So to disable the `Posts` from the global search using the repository property we do:

```php
// PostRepository.php
public static bool $globallySearchable = false;
```

### Paginate global search

You can limit the number of results that are returned in the global search by overriding the `globalSearchResults` property on the resource:

```php
// PostRepository.php
public static int $globalSearchResults = 5;
```

<alert type="success"> 

Restify has built in support for laravel scout, so it will initialize the query using scout if you have setup it for the model.

</alert>

### Customize global search

The default global search response looks like this:

```json
{
  "data": [
    {
      "repositoryName": "users",
      "repositoryTitle": "Users",
      "title": "Mrs. Lucie Parker Jr.",
      "subTitle": null,
      "repositoryId": 1,
      "link": "/api/restify/users/1"
    }
  ]
}
```

Where the `title` is the repository column defined by the `$title` property. So you can customize it:

```php
// UserRepository.php

public static string $title = 'email';
```

The `subTitle` could be customized by overriding the `subtitle` method. The returned value will be displayed here:

```php
// UserRepository.php
public function subtitle(): ?string
{
    return 'User email: ' . $this->model()->email;
}
```

The `repositoryTitle` could be customized by overriding the `label` method or by defining the `$label` property. This will customize the displayed repository title in the global search response:

```php
// UserRepository.php
public static string $label = 'Custom Repository Title';

// Or using the label method for dynamic titles:
public static function label(): string
{
    return 'Dynamic Title';
}
```

## Repository Search

The repository search works in a similar way as [global search](#global-search), however in this case the endpoint refers to the repository and the search will be applied for a certain repository.

Say we want to search users by their `email` and `name`:

```php
class UserRepository extends Repository
{
    public static array $search = ['name', 'email'];
```

So the endpoint will scope the the `users` repository now:

```http request
GET: /api/restify/users?search="John Doe"
```

## Case-sensitive search

By default, Restify search is case-sensitive. You can change this behavior by changing the configuration:

```php
// restify.php

  'search' => [
      /*
      | Specify either the search should be case-sensitive or not.
      */
      'case_sensitive' => false,
  ],
```

### Custom search filter

The search could be customized by creating a class that extends the `\Binaryk\LaravelRestify\Filters\SearchableFilter`: 

```php
use Binaryk\LaravelRestify\Filters\SearchableFilter;

class CustomTitleSearchFilter extends SearchableFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
          return $query->orWhere('name', 'like', "%$value%");
    }
}
```

In the `filter` method you can define your own filtering over the `$query` builder and then attach the class instance to a column:

```php
public static function searchables(): array
{
    return [
        'title' => CustomTitleSearchFilter::make(),
    ];
}
```

<alert type="danger">

As soon as you define the `searchables` method into the repository, the `$search` array is not taken into consideration anymore. So make sure you return all available search fields from this method.

</alert>

## Match

Matching by specific attributes may be useful if you want an exact matching.

Repository configuration:

```php
class PostRepository extends Repository
{
    public static array $match = [
        'id' => 'int',
        'title' => 'string',
    ];
}
```

As we may notice the match configuration is an associative array, defining the attribute name and type mapping.

Available types:

- [text (or `string`)](#match-string)
- [bool](#match-bool)
- [int (or `integer`)](#match-int)
- [datetime](#match-datetime)
- [between](#match-between)
- [array](#match-array)

When performing the request you may pass the match field and value as query params:

```http request
GET: /api/restify/posts?id=1
```

or by title:

```http request
GET: /api/restify/posts?title="Some title"
```

### Match string

Definition: 

```php
class PostRepository extends Repository
{
    public static array $match = [
        'title' => 'string',
    ];
}
```

Request: 

```http request
GET: /api/restify/posts?title="Title"
```

### Match bool

Definition: 

```php
class PostRepository extends Repository
{
    public static array $match = [
        'active' => 'bool',
    ];
}
```

Request: 

```http request
GET: /api/restify/posts?active=true
```

### Match int

Definition: 

```php
class PostRepository extends Repository
{
    public static array $match = [
        'id' => 'int',
    ];
}
```

Request: 

```http request
GET: /api/restify/posts?id=1
```

### Match datetime

The `datetime` filter add behind the scene an `whereDate` query.

```php
class PostRepository extends Repository
{
    public static array $match = [
        'published_at' => 'datetime',
    ];
}
```

Request:

```http request
GET: /api/restify/posts?published_at=2020-12-01
```

If the request contains two dates instead of one, it will perform a `whereBetween` query:

```http request
GET: /api/restify/posts?published_at=2020-12-01,2021-01-01
```

Eloquent will do: 

```php
$query->whereBetween('published_at', ['2020-12-01', '2021-01-01']);
```

### Match between

The `between` match works similarly as the `whereBetween` Eloquent method: 

```php
class PostRepository extends Repository
{
    public static array $match = [
        'id' => 'between',
        'published_at' => 'between',
    ];
}
```

Request:

```http request
GET: /api/restify/posts?published_at=2021-09-16,2021-11-16
```

So it will return all posts published between the first and the second dates. It works with `integer` as well: 

```http request
GET: /api/restify/posts?id=1,20
```

Match all available `ids` between `1` and `20`.

### Match array

Match also accept a list of elements in the query param:

```php
class PostRepository extends Repository
{
    public static $match = [
        'id' => 'array'
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

### Match null

All match types accept `null` as a value, and check add `whereNull` to the query:

```http request
GET: /api/restify/posts?published_at=null
```

### Match negation

All match types accept a negation, so you can negate the column match by simply adding the `-` (minus) sign before the field:

```http request
GET: /api/restify/posts?-id=1,2,3
```

This will return all posts where doesn't have the `id` in the `[1,2,3]` list.

You can apply `-` (negation) for every match:

```http request
GET: /api/restify/posts?-title="Some title"
```

This will return all posts that doesn't contain `Some title` substring.

### Custom match filter

Sometimes you may have a large logic into a match. To allow this, `Restify` provides a declarative way to define `matchers`. For this purpose you should define a class, that extends the `Binaryk\LaravelRestify\Filters\MatchFilter`:

```php
use Binaryk\LaravelRestify\Filters\MatchFilter;

class ActivePostMatchFiler extends MatchFilter
{
    public function filter(RestifyRequest $request, Builder | Relation $query, $value)
    {
        // your logic here
    }
}
```

The next step is to return this class instance from the `matchers` method:

```php
// PostRepository.php
public static function matches(): array
{
    return [
        'active' => ActivePostMatchFiler::make(),
    ];
}
```

<alert type="danger">

As soon as you define the `matches` method into the repository, the `$match` array is not taken into consideration anymore. So make sure you return all available matches from this method.

</alert>

### Partial match

The match filters 1:1 match, however, when you're looking for a substring into a text, you might need to partially match it.

This could be done using the `Binaryk\LaravelRestify\Filters\MatchFilter` class:

```php
public static function matches(): array
{
    return [
        'title' => MatchFilter::make()->setType('text')->partial()
    ];
}
```

### Get available matches

You can use the following request to get all repository matches:

```http request
/api/restify/posts/filters?only=matches
```
