# Restify Exception Handler 
When creating an API the exceptions usually should be handled and resolved before sending to the client. 
This is usually done in the Laravel ExceptionHandler which transform the exception in a RestResponse and debug it for you into: 
- line
- code
- file
- stack trace


## Disable Restify exception handler
However Restify gives you a handy exception handler which is configured in the 
`restify.exception_handler` you may want to delegate the exception handling to your application ExceptionHandler for more control. 

You can do that changing config by nullifying it or replace with another handler class: 

```php
[
    // config/restify.php
    ...

    'exception_handler' => null
]
```

## Intercept exceptions

Intercepting exceptions for a specific request is breeze to do with Restify.
Let's assume we have the store users controller action:

```php
use App\User;
use Binaryk\LaravelRestify\Controllers\RestController;
use Binaryk\LaravelRestify\Restify;use Illuminate\Http\Request;use Illuminate\Http\Response;use Illuminate\Support\Facades\Log;

class UserController extends RestController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Intercept the exception handler and log the exception message
        Restify::exceptionHandler(function ($request, Exception $exception) {
            Log::alert($exception->getMessage());
            return Response::make('Something went wrong', $exception->getCode());
        });
        
        return $this->response(User::create($request->all()));
    }
}
```

As we can see the `exceptionHandler` callback receive the `$request` and thrown `$exception`. 
