# Filtering entities

Laravel Restify provides configurable and powerful way of filtering over entities. 

All of your repositories has search/filtering out of the box. You just have to specify the configuration for that.

## Search

If you want search for some specific fields from a model, you have to define these fields in the `$search` static 
property:

```php
class PostRepository extends Repository
{
    public static $search = ['id', 'title'];
```

Now the `Post` entity is searchable by `id` and `title`, so you could use `search` query param for filtering the index 
request: 

```http request
GET: /restify-api/posts?search="Test title"
```

## Match attribute

Matching by specific attributes may be useful if you want an exact matching. Repository
configuration:

```php
class PostRepository extends Repository
{
    public static $match = [
        'id' => Binaryk\LaravelRestify\Contracts\RestifySearchable::MATCH_INTEGER,
        'title' => Binaryk\LaravelRestify\Contracts\RestifySearchable::MATCH_TEXT,
    ];      
```

As we may notice the match configuration is an associative array, defining the attribute name and type mapping. 

Available types:

- text (or `string`)
- bool
- int (or `integer`)

When performing the request you may pass the match field and value as query params:

```http request
GET: /restify-api/posts?id=1
```

or by title:

```http request
GET: /restify-api/posts?title="Some title"
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
 
 Sorting DESC requires a minus sign before the attribute name:
 
 ```http request
GET: /restify-api/posts?sort=-id
```

 Sorting ASC:
 
 ```http request
GET: /restify-api/posts?sort=id
```

or with plus sign before the field:

 ```http request
GET: /restify-api/posts?sort=+id
```

## Eager loading - aka related

When get a repository index or details about a single entity, often we have to get the related entities (we have access to).
This eager loading is configurable by Restify as: 

```php
public static $related = ['user'];
```

This means that we could use `posts` query for eager loading posts:

```http request
GET: /restify-api/related?with=user
```
