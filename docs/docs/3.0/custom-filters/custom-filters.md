# Custom filters

Laravel Restify has built in filters for usual search or matching. But what if you need some custom filtering. Restify ships with an easy way to implement your own custom filters.


[[toc]]

## Date filters

Defining the filter:

```php
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\TimestampFilter;

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
$filters = atob(JSON.encode([
    {
        'class': 'App\Restify\Filters\\CreatedAfterDateFilter',
        'value': moment()->timestamp
    }
]))

const  response = await axios.get('restify-api/posts?filters=' + $filters);
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
