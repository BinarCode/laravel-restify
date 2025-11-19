---
title: Advanced filters
menuTitle: Advanced filters
category: Search & Filters
position: 12
---

Restify has [base filters](/search/basic-filters) for usual `search` or `matching`. 

<alert type="success"> 
Advanced filters will help you to build your own filters from scratch.
</alert>

## Definition

To declare an advanced filter you should create a class that extends the `Binaryk\LaravelRestify\Filters\AdvancedFilter`.

Say we have a filter that filters all ready to publish posts:

```php
use Binaryk\LaravelRestify\Filters\AdvancedFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReadyPostsFilter extends AdvancedFilter 
{
    public function filter(RestifyRequest $request, Relation|Builder $query, $value)
    {
        // TODO: Implement filter() method.
    }

    public function rules(Request $request): array
    {
        return [];
    }

};
```

### Register filter

Then add the filter to the repository `filters` method: 

```php
// PostRepository.php
public function filters(RestifyRequest $request): array
{
    return [
        ReadyPostsFilter::new(),
    ];
}
```

### Authorize filter

You can authorize certain filters to be active for specific users: 

```php
// PostRepository.php
public function filters(RestifyRequest $request): array
{
    return [
        ReadyPostsFilter::new()->canSee(
            fn($request) => $request->user()->isAdmin()
        ),
    ];
}
```

### Apply advanced filter

To apply an advanced filter, the frontend has to send the `filters` query param with a base64 encoded filter:

```javascript
const filters = btoa(JSON.stringify([
    {
        'key': 'ready-posts-filter',
        'value': null,
    }
]))

const  response = await axios.get(`api/restify/posts?filters=${filters}`);
```

The frontend has to encode into base64 an array of filters. Each filter contains 2 things:

- `key` - which is the `ke-bab` form of the filter class name, or a custom `$uriKey` [defined in the filter](#custom-uri-key)

- `value` - this is optional, and represents the value the advanced filter will as a third argument in the `filter` method

## Apply advanced filters via POST Request (Version 9.3.0+)

Starting from version 9.3.0, Laravel Restify introduces the ability to apply advanced filters using a POST request. This enhancement simplifies the process of sending complex filter payloads without the need for base64 encoding. Now, you can send the filters directly as JSON in the request body:

```javascript
const filters = [
    {
        'key': 'ready-posts-filter',
        'value': null,
    }
];

const  response = await axios.post(`api/restify/posts/apply-restify-advanced-filters`, { filters });
```


### Custom uri key

Since your class names could change along the way, you can define a `$uriKey` property to your filters, so the frontend will use always the same `key` when applying a filter:

```php
class ReadyPostsFilter extends AdvancedFilter 
{
    public static $uriKey = 'ready-posts';

    //...

};
```

### Custom title

```php
class ReadyPostsFilter extends AdvancedFilter 
{
    public static $title = 'Ready to publish posts';

    //...

};
```

### Custom description

```php
class ReadyPostsFilter extends AdvancedFilter 
{
    public static $description = 'Filter all posts that are ready to publish';

    //...

};
```

### Custom meta

```php
class ReadyPostsFilter extends AdvancedFilter 
{
   public function meta(): array
   {
      return [
          'icon' => 'icon',
          'color' => 'red',
          'operators' => [
              'like' => 'Like', 
              'eq' => 'Equal', 
          ]
      ];
   } 
};
```

Meta will be rendered key/value in the frontend:

```json
{
    ...
    "icon": "icon",
    "color": "red",
    "operators": {
        "like": "Like",
        "eq": "Equal"
    }
}
```

### Advanced filter value

The third argument of the `filter` method is the raw value send by the frontend. Sometimes it might be an array, so you have to get the value using array access: 

```php
$value['activation']['active']
```

To avoid this, there is an `input` method defined into the parent class, so you can use: 

```php
 public function filter(RestifyRequest $request, Relation|Builder $query, $value)
{
    $value = $this->input('activation.active', false);
}
```

This method gets a default value as a second parameter in case the frontend didn't define it.


### Advanced filter rules

The `rules` method return an associative array with laravel rules for the payload the frontend should send in the `value` property for this specific filter. The payload is validated right before it gets to the filter method:

```php
public function rules(Request $request): array
{
    return [
        'created_at' => ['required'],
    ];
}
```

So the frontend should send the `created_at` value:

```javascript
{
    'key': 'ready-posts-filter',
    'value': { created_at: '2021-01-01' }
}
```

And you can get this value into the `filter` method using the [advanced filter value](#advanced-filter-value):

```php
 $value = $this->input('created_at', now());
```



## Variations

Restify ships a few types of build in filter classes you can extend for specific needs.

### Date filters

Defining the filter:

```php
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Filters\TimestampFilter;

class CreatedAfterDateFilter extends TimestampFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->whereDate('created_at', '>', $value);
    }
}
```

Using filter:

```php
public function filters(RestifyRequest $request)
{
    return [
        CreatedAfterDateFilter::new(),
    ];
}
```

JavaScript implementation:

```javascript
const filters = btoa(JSON.stringify([
    {
        'key': 'created-after-date-filter',
        'value': moment()->timestamp
    }
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```


### Select Filters

Defining the filter:

```php
<?php
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Filters\SelectFilter;
use Illuminate\Http\Request;

class SelectCategoryFilter extends SelectFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        // $value could be 'movie' or 'article'
        $query->where('category', $value);
    }

    public function options(Request $request)
    {
        return [
            'Movie category' => 'movie',

            'Article Category' => 'article',
        ];
    }
}
```

Using filter:


```php
// App/Restify/PostRepository.php
public function filters(RestifyRequest $request)
{
    return [
        SelectCategoryFilter::new(),
    ];
}
```

JavaScript implementation:

```javascript
const filters = btoa(JSON.stringify([
    {
        'key': 'select-category-filter',
        'value': 'article'
    }
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```

### Boolean filter

Defining the filter:
```php
<?php
use Binaryk\LaravelRestify\BooleanFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Http\Request;

class ActiveBooleanFilter extends BooleanFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        $query->where('is_active', $value['is_active']);
    }

    public function options(Request $request)
    {
        return [
            'Is Active' => 'is_active',
        ];
    }
}

```
Using filter:
```php
// App/Restify/PostRepository.php
public function filters(RestifyRequest $request)
{
    return [
        ActiveBooleanFilter::new(),
    ];
```

JavaScript implementation:

```javascript
const filters = btoa(JSON.stringify([
    {
        'key': 'active-boolean-filter',
        'value': {
            'is_active': true,
        }
    }
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```

## Multiple filters

You can combine filters as you prefer:


```javascript
const filters = btoa(JSON.stringify([
    {
        'key': 'active-boolean-filter',
        'value': {
            'is_active': true,
        }
    }, 
    {
        'key': 'select-category-filter',
        'value': 'article'
    },
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```

## Get available filters

```javascript
await axios.get('resitfy-api/posts/filters');
```

The response will look like this:

```json
{
  "data": [
    {
      "key": "active-boolean-filter",
      "type": "boolean",
      "options": [
        {
          "label": "Is Active",
          "property": "is_active"
        }
      ]
    },
    {
      "key": "select-category-filter",
      "type": "select",
      "options": [
        {
          "label": "Movie category",
          "property": "movie"
        },
        {
          "label": "Article Category",
          "property": "article"
        }
      ]
    },
    {
      "key": "created-after-date-filter",
      "type": "timestamp",
      "options": []
    },
    {
      "key": "email",
      "type": "value",
      "description": "Email",
      "label": "Email",
      "meta": {
        "operator": "like"
      }
    }
  ]
}
```

Along with custom filters, you can also include in the response the primary filters (as matches), by using `?include` query param: 

```http request
/api/restify/posts/filters?include=matches,searchables,sortables
```

## Handling Additional Payload Data in Advanced Filters

In some scenarios, you might want to send additional data beyond the standard key and value in your filter payload. For instance, you may need to specify an operator or a column to apply more complex filtering logic. Laravel Restify Advanced Filters provide a way to handle these additional payload fields using the `$this->rest()` method.

**Example Payload**

Consider the following payload:
```json
const filters = [
    {
        'key': ValueFilter::uriKey(),
        'value': 'Valid%',
        'operator' => 'like',
        'column' => 'description',
    }
];

const response = await axios.post(`api/restify/posts/apply-restify-advanced-filters`, {filters});
```

In this payload, besides the standard key and value, we are also sending operator and column. The operator specifies the type of SQL operation, and the column specifies the database column to filter.

Using `$this->rest()` to Access Additional Data

To handle these additional fields in your filter class, you need to ensure they are accessible via the `$this->rest()` method. Here is how you can achieve that:

```php
class ValueFilter extends AdvancedFilter
{
    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        $operator = $this->rest('operator');
        $column = $this->rest('column');

        $query->where($column, $operator, $value);
    }

    public function rules(Request $request): array
    {
        return [];
    }
}
```

