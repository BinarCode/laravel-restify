# Field

Field is basically the model attribute representation. Each Field generally extend the Field class from the Restify. 
This class ships a variety of mutators, interceptors, validators chaining methods you can use for defining your attribute
according with your needed.

To add a field to a repository, we can simply add it to the repository's fields method. 
Typically, fields may be created using their static make method. This method accepts the underlying database column as 
argument: 

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
        Field::make('email')->rules('required')->storingRules('unique:users')->messages([
            'required' => 'This field is required.',
        ]),
        Field::make('password')->storeCallback(function ($value) {
                return Hash::make($value);
            })->rules('required')->storingRules('confirmed'),
    ];
}
```

# Validation

There is a gold rule saying - catch the exception as soon as possible on it's request way. 
Validations are the first bridge of your request information, it would be a good start to validate 
your input so you don't have to worry about payload anymore.

## Attaching rules

Validation rules could be add by chaining the `rules` method to attach [validation rules](https://laravel.com/docs/validation#available-validation-rules)
to the field: 

```php
Field::make('email')->rules('required'),
```

Of course, if you are leveraging Laravel's support for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), 
you may attach those to resources as well:

```php
Field::make('email')->rules('required', new CustomRule),
```

Additionally, you may use [custom Closure rules](https://laravel.com/docs/validation#using-closures) 
to validate your resource fields:

```php
Field::make('email')->rules('required', function($attribute, $value, $fail) {
    if (strtolower($value) !== $value) {
        return $fail('The '.$attribute.' field must be lowercase.');
    }
}),
```

## Storing Rules 

If you would like to define rules that only apply when a resource is being storing, you may use the `storingRules` method:

```php
Field::make('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

## Update Rules

Likewise, if you would like to define rules that only apply when a resource is being updated, you may use the `updatingRules` method.

```php
Field::make('email')->updatingRules('required', 'email');
```


# Interceptors
However the default storing process is done automatically, sometimes you may want to take the control over it. 
That's a breeze with Restify, since Field expose few useful chained helpers for that.

## Fill callback

There are two steps before the value from the request is attached to model attribute. 
Firstly it goes to the `fillCallback` and secondly to the `storeCallback`. You may intercept each of those with closures.

```php
Field::make('title')
    ->fillCallback(function (RestifyRequest $request, $model, $attribute) {
        $model->{$attribute} = strtoupper($request->get('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.


## Store callback

Another handy interceptor is the `storeCallback`, this is the step immediately before attaching the value from the request to the model attribute:

This interceptor may be useful to just modify the value passed through the `$request`.

```php
Field::make('password')->storeCallback(function ($value) {
        return Hash::make($value);
    });
```
