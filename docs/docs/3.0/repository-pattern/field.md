# Field

Field is basically the model attribute representation. Each Field generally extends the `Binaryk\LaravelRestify\Fields\Field` class from the Laravel Restify. 
This class ships a variety of mutators, interceptors, validators chaining methods you can use for defining your attribute.

To add a field to a repository, we can simply add it to the repository's fields method. 
Typically, fields may be created using their static `new` or `make` method. These methods accept the underlying database column as argument: 

```php

use Illuminate\Support\Facades\Hash;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

/**
 * @param  RestifyRequest  $request
 * @return array
 */
public function fields(RestifyRequest $request)
{
    return [
        Field::new('email')->rules('required')->storingRules('unique:users')->messages([
            'required' => 'This field is required.',
        ]),
        Field::new('password')->storeCallback(function ($value) {
                return Hash::make($value);
            })->rules('required')->storingRules('confirmed'),
    ];
}
```

# Validation

There is a gold rule saying - catch the exception as soon as possible on its request way. 
Validations are the first bridge of your request information, it would be a good start to validate 
your input. So you don't have to worry about the payload anymore.

## Attaching rules

Validation rules could be adding by chaining the `rules` method to attach [validation rules](https://laravel.com/docs/validation#available-validation-rules)
to the field: 

```php
Field::new('email')->rules('required'),
```

Of course, if you are leveraging Laravel's support for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), 
you may attach those to resources as well:

```php
Field::new('email')->rules('required', new CustomRule),
```

Additionally, you may use [custom Closure rules](https://laravel.com/docs/validation#using-closures) 
to validate your resource fields:

```php
Field::new('email')->rules('required', function($attribute, $value, $fail) {
    if (strtolower($value) !== $value) {
        return $fail('The '.$attribute.' field must be lowercase.');
    }
}),
```

## Storing Rules 

If you would like to define rules that only apply when a resource is being storing, you may use the `storingRules` method:

```php
Field::new('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

## Update Rules

Likewise, if you would like to define rules that only apply when a resource is being updated, you may use the `updatingRules` method.

```php
Field::new('email')->updatingRules('required', 'email');
```


# Interceptors
However, the default storing process is automatically, sometimes you may want to take the control over it. 
That's a breeze with Restify, since Field expose few useful chained helpers for that.

## Fill callback

There are two steps before the value from the request is attached to model attribute. 
Firstly it is get from the application request, and go to the `fillCallback` and secondly, 
the value is transforming by the `storeCallback` or `updateCallback`:

You may intercept each of those with closures.

```php
Field::new('title')
    ->fillCallback(function (RestifyRequest $request, $model, $attribute) {
        $model->{$attribute} = strtoupper($request->get('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.


## Store callback

Another handy interceptor is the `storeCallback`, this is the step immediately before attaching the value from the request to the model attribute:

This interceptor may be useful for modifying the value passed through the `$request`.

```php
Field::new('password')->storeCallback(function ($value) {
        return Hash::new($value);
    });
```

## Update callback

```php
Field::new('password')->updateCallback(function ($value) {
        return Hash::new($value);
    });
```
## Index Callback

Sometimes you may want to transform some attribute from the database right before it is returned to the frontend.

Transform the value for the index request:

```php
Field::new('password')->indexCallback(function ($value) {
    return Hash::new($value);
});
```
## Show callback

Transform the value for the show request:

```php
Field::new('password')->showRequest(function ($value) {
    return Hash::new($value);
});
```

## Append Callback

Very often there is necessary to store a field as `auth()->user()->id`. This field could not be passed from the 
frontend:

```php
Field::new('user_id')->hidden()->append(auth()->user()->id);
```

or using a closure:

```php
Field::new('user_id')->hidden()->append(function(RestifyRequest $request, $model, $attribute) {
    return auth()->user()->id;    
});
```

## Field label

- Field label, so you can replace a field attribute:
```
Field::new('created_at')->label('sent_at')
```
- Field can be setup as hidden:
```
Field::new('token')->hidden(); // this will not be visible 
```
- Field can have append value, to append information like auth user, or any other relationships:
```
Field::new('user_id')->hidden()->append(auth()->user()->id); // this will not be visible, but will be stored
```

- Related repositories no longer requires a `viaRelationship` query param, as it will get the default one from the main repository:
Before:

` axios.get('/restify/users?viaRelationship=users&viaRepositoryId=1&viaRepository=companies')`

After:

` axios.get('/restify/users?viaRepositoryId=1&viaRepository=companies')`
