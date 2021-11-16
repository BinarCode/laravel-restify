---
title: Advanced filters
menuTitle: Advanced filters
category: Search & Filters
position: 12
---

Laravel Restify has built in filters for usual search or matching. But what if you need some custom filtering. 

Restify ships with an easy way to implement your own custom filters.

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

Then add the filter to the repository `filters` method: 

```php
// PostRepository.php
public function filters(RestifyRequest $request): array
{
    return [
        ReadyPostsFilter::make(),
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
        ReadyPostsFilter::make()->canSee(
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

- `value` - this is optional, and represents the value the advanced filter will as a third argument in the `filter` method.

### Custom uri key

Since your class names could change along the way, you can define a `$uriKey` property to your filters, so the frontend will use always the same `key` when applying a filter:

```php
class ReadyPostsFilter extends AdvancedFilter 
{
    public static $uriKey = 'ready-posts';

    //...

};
```

### Advanced filter value

The third argument of the `filter` method is the raw value send by the frontend. Sometimes it might be an array, so you have to get the value using array access: 

```php
$value['activation']['active']
```

Well, there is a `input` method defined into the parent class you can use, that facilitate this: 

```php
 public function filter(RestifyRequest $request, Relation|Builder $query, $value)
{
    $value = $this->input('activation.active', false);
}
```

This method gets a default value as a second parameter in case the frontend didn't define it.

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
        'class': 'App\\Restify\\Filters\\CreatedAfterDateFilter',
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
        'key': 'App\\Restify\\Filters\\SelectCategoryFilter',
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
        'key': 'App\\Restify\\Filters\\SelectCategoryFilter',
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
      "class": "Binaryk\\LaravelRestify\\Tests\\Fixtures\\Post\\ActiveBooleanFilter",
      "type": "boolean",
      "options": [
        {
          "label": "Is Active",
          "property": "is_active"
        }
      ]
    },
    {
      "class": "Binaryk\\LaravelRestify\\Tests\\Fixtures\\Post\\SelectCategoryFilter",
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
      "class": "Binaryk\\LaravelRestify\\Tests\\Fixtures\\Post\\CreatedAfterDateFilter",
      "type": "timestamp",
      "options": []
    }
  ]
```

Along with custom filters, you can also include in the response the primary filters (as matches), by using `?include` query param: 

```http request
/api/restify/posts/filters?include=matches,searchables,sortables
```

