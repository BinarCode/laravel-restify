---
title: AI Solution
menuTitle: AI Solution
description: AI Solution
category: Advanced
position: 14
---

Inspired by the [Marcel's Article](https://beyondco.de/blog/ai-powered-error-solutions-for-laravel).

## Generate solution

Restify can generate an AI based solution to your problem. In order to enable that you need to extend the `App\Exceptions\Handler` with the `Binaryk\LaravelRestify\Exceptions\RestifyHandler`: 

```php
use Binaryk\LaravelRestify\Exceptions\RestifyHandler;
use Throwable;

class Handler extends RestifyHandler
{
    //...
}
```

<alert type="warning">
This feature is only enabled when the `app.debug` is set to `true`.
</alert>


This feature is using the [openai-php/laravel](https://github.com/openai-php/laravel#get-started), you should also publish the config file:

```
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

and set the `OPENAI_API_KEY` in the `.env` file.

The OpenAI key can be obtained from [here](https://platform.openai.com/account/api-keys).


Now the solution to your problems will automatically appear in the response: 

```json
{
    "restify-solution": "Line 67 in DocumentRepository.php file has an error because the method `resolveUsingFullPath()` is not defined. The code should look like this:\n```\n->resolveUsingTemporaryUrl($request->boolean('temporary'))\n```\n",
    "message": "Call to undefined method Binaryk\\LaravelRestify\\Fields\\File::resolveUsingFullPath()",
    "exception": "Error",
    "file": "/Users/eduardlupacescu/Sites/binarcode/erp/app/Restify/DocumentRepository.php",
    "line": 67,
    "trace": [
...
}
```

## Disable solution


If you want to disable the solution feature you can set the `restify.ai_solution` to `false` in the `config/restify.php` file so Restify will not call the OpenAI API even you extended the exception handler. This might be useful in automated tests or other environments:

```php
// config/restify.php
'ai_solutions' => true,
```
