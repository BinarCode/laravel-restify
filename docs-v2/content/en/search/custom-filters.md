---
title: Custom Filters
menuTitle: Custom Filters
category: Search & Filters
position: 12
---

Laravel Restify has built in filters for usual search or matching. But what if you need some custom filtering. Restify ships with an easy way to implement your own custom filters.

## Date filters

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


## Select Filters

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
        'class': 'App\\Restify\\Filters\\SelectCategoryFilter',
        'value': 'article'
    }
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```

## Boolean filter

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
        'class': 'App\\Restify\\Filters\\ActiveBooleanFilter',
        'value': {
            'is_active': true,
        }
    }
]))

const  response = await axios.get('api/restify/posts?filters=' + filters);
```

## Filters authorization

You can unauthorized filter usage by using laravel policies:

```php
public function filters(RestifyRequest $request)
{
    return [
        ActiveBooleanFilter::new()->canSee(fn (RestifyRequest $request) => $request->user()->can('seeBooleanFilters')),
    ];
}
```

## Multiple filters

Definitely you can combine filters as you prefer:


```javascript
const filters = btoa(JSON.stringify([
    {
        'class': 'App\\Restify\\Filters\\ActiveBooleanFilter',
        'value': {
            'is_active': true,
        }
    }, 
    {
        'class': 'App\\Restify\\Filters\\SelectCategoryFilter',
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

