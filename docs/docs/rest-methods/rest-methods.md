[[toc]]

## Introduction 
The API response format must stay consistent along the application.
Restify provides several different approaches to respond consistent to the application's incoming request. 
By default, Restify's base rest controller class uses a `RestResponse` structure which provides a convenient 
method to respond to the HTTP request with a variety of powerful magic methods.

## Restify Response Quickstart
To learn about Restify's powerful response features, let's look at a complete example of responding a 
request and returning the data back to the user.

### Defining The Route
First, let's assume we have the following routes defined in our `routes/api.php` file:

```php
Route::post('books', 'BookController@store');
Route::get('books/{id}', 'BookController@show');
```

The `GET` route will return back a book for the given `id`.

### Creating The Controller

Next, let's take a look at a simple `API` controller that handles this route. We'll leave the `show` and `store` methods empty for now:
```php
<?php

namespace App\Http\Controllers;

use Binaryk\LaravelRestify\Controllers\RestController;

class BookController extends RestController
{
    /**
     * Store a newly created book in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        // The only way to do great work is to love what you do.
    }

    /**
     * Display the book entity
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        // Very little is needed to make a happy life.
    }
}
```

### Writing The API Response Logic

Now we are ready to fill in our `show` method with the logic to respond with the book resource. 
To do this, we will use the `respond` method provided by the parent `Binaryk\LaravelRestify\Controllers\RestController` class. 
A JSON response will be sent for `API` request containing `data` and `errors` properties. 

To get a better understanding of the `respond` method, let's jump back into the `show` method:

```php
    /**
     * Display the book entity
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return $this->respond(Book::find($id));
    }
```

As you can see, we pass the desired data into the `respond` method. This method will wrap the passed data into a 
JSON object and attach it to the `data` response property. 

### Receiving API Response

Once the `respond` method wrapping the data, the HTTP request will receive back a response having always the 
structure: 

```json
{
  "data": {...},
  "errors": [...]
}
```

### Additional Response Methods
In addition to the default `respond` method, the parent `RestController` provides a powerful `response` factory method.
To understand this let's return back to our `store` method from the `BookController`:

```php
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        return $this->response(...);
    }
```

The `response()` method will be an instance of `Binaryk\LaravelRestify\Controllers\RestResponse`. For more information on working with this object instance, 
[check out its documentation](#rest-response-methods).

:::tip

When using the proxy `response` method, always be sure to call the `respond()` method at the end of the methods chain calls:
```php
$this->response()
->data(['book' => $book])
->message('Silence is golden')
->errors(['An error occur'])
->respond();
```
:::

### Displaying Response Errors

As we saw above, the response always contains an `errors` property. This can be either an empty array, or a list with errors.
For example, what if the incoming request parameters do not pass the given validation rules? This can be handled by the `errors` proxy
method:

```php
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'title' => 'required|unique:books|max:255',
            ]);

            // The book is valid
        } catch (ValidationException $exception) {
            // The book is not valid
            return $this->errors($exception->errors());
        }
    }
```

And returned `API` response will have the `400` HTTP code and the following format:

```json
{
    "errors": {
        "title": [
            "The title field is required."
        ]
    }
}
```

## Optional Attributes

By default Restify returns `data` and `errors` attributes in the API response, but what if we have to 
send some custom attributes. In addition to generating default fields, you may add extra fields to the 
response by using `setAttribute` method from the `RestResponse` object:

```php
    return $this->response()
        ->data($book)
        ->setAttribute('related_authors', [ 'William Shakespeare', 'Agatha Christie', 'Leo Tolstoy' ])
        ->respond();
```

## Hiding Default Attribute

Restify has a list of predefined attributes: `'line', 'file', 'stack', 'data', 'errors'`. Some of those are hidden in production: `'line', 'file', 'stack'`, 
since they are only used for tracking exceptions.
As we noticed, the API response consistency is very important for the client implementation. That's why the `data` and 
`errors` attributes remain always. If you would like the API response to not contain any of these fields (or hiding a specific one, `errors` for example),
 this can be done by setting the: 

```php
    RestResponse::$RESPONSE_DEFAULT_ATTRIVBUTES = [];
```




## Rest Controller Methods

## Rest Response Methods

The `$this->response()` returns an instance of `Binaryk\LaravelRestify\Controllers\RestResponse`. This expose multiple 
magic methods for your consistent API response. 

## Available Magic Methods
-   [message](#message)
-   [auth](#auth)
-   [refresh](#refresh)
-   [created](#created)
-   [deleted](#deleted)
-   [blank](#blank)
-   [error](#error)
-   [invalid](#invalid)
-   [unauthorized](#unauthorized)
-   [forbidden](#forbidden)
-   [missing](#missing)
-   [success](#success)
-   [unavailable](#unavailable)
-   [throttle](#throttle)
-   [errors](#errors)
-   [addError](#addError)
-   [code](#code)
-   [line](#line)
-   [file](#file)
-   [stack](#stack)


### auth

### refresh

### created

### deleted

### blank

### error

### invalid

### unauthorized

### forbidden

### missing

### success

### unavailable

### throttle

### errors
### addError
Returning a response with empty data `[]` and `message`

```php
$this->message('Silence is golden');
```

### Message
Returning a response with empty data `[]` and `message`
```php
$this->message('Silence is golden');
```

## Extra response attributes
`setAttribute` method could be used for adding extra fields in the response. E.g. 
