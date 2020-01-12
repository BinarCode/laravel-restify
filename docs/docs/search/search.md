# Filtering entities

Laravel Restify provides configurable and powerful way of filtering over entities. 

- Prerequisites

In order to make a model searchable, it should implement the `Binaryk\LaravelRestify\Contracts\RestifySearchable` contract.
After running this command, add the `Binaryk\LaravelRestify\Traits\InteractWithSearch` trait to your model. 
This trait will provide a few helper methods to your model which allow you to filter.

:::tip
The searchable feature is available as for the Restify generated endpoints as well as from a custom Controller searching,
`$this->search(Model::class)`
:::

## Search

If you want search for some specific fields from a model, you have to define these fields in the `$search` static 
property:

```php
use Illuminate\Database\Eloquent\Model;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;

class Post extends Model implements  RestifySearchable
{
    use InteractWithSearch;

    public static $search = ['id', 'title'];
```

Now the `Post` entity is searchable by `id` and `title`, so you could use `search` query param for filtering the index 
request: 

```http request
GET: /restify-api/posts?search="Test title"
```

## Match

Matching by specific attributes may be useful if you want an exact matching. Model 
configuration:

```php
use Illuminate\Database\Eloquent\Model;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;

class Post extends Model implements  RestifySearchable
{
    use InteractWithSearch;

    public static $search = ['id', 'title'];

    public static $match = ['id' => 'int', 'title' => 'string'];

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
use Illuminate\Database\Eloquent\Model;
use Binaryk\LaravelRestify\Traits\InteractWithSearch;
use Binaryk\LaravelRestify\Contracts\RestifySearchable;

class Post extends Model implements  RestifySearchable
{
    use InteractWithSearch;

    public static $search = ['id', 'title'];

    public static $match = ['id' => 'int', 'title' => 'string'];

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
