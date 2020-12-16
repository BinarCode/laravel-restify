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

There may be situations when the filter you want to apply not necessarily is a database attributes. You can use a Closure to handle this easily:

```php
// UserRepository
public static function getMatchByFields()
{
    return [
        'is_active' => function ($request, $query) {
            if ($request->boolean('is_active')) {
               $query->whereNotNull('email_verified_at');
           } else {
               $query->whereNull('email_verified_at');
            }       
        }
    ];
}
```

So now you can query this: 

```http request
GET: /api/restify/users?is_active=true
```

### Match definition

You can implement a match filter definition, and specify for example related repository:

```php
  public static $match = [
        'title' => 'string',
        'user_id' => MatchFilter::make()
            ->setType('int')
            ->setRelatedRepositoryKey(UserRepository::uriKey()),
];
```
When you will list this filter (with `posts/filters?only=matches`), you will get: 

```json
  {
      "class": "Binaryk\LaravelRestify\Filters\MatchFilter"
      "key": "matches"
      "type": "string"
      "column": "title"
      "options": []
    },
    {
      "class": "Binaryk\LaravelRestify\Filters\MatchFilter"
      "key": "matches"
      "type": "int"
      "column": "user_id"
      "options": []
      "related_repository_key": "users"
      "related_repository_url": "//users"
    }
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

