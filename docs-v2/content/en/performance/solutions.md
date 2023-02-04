---
title: AI Solution
menuTitle: AI Solution
description: AI Solution
category: Advanced
position: 14
---

## Generate solution

Restify can generate an AI based solution to your problem. In order to enable that you need to enable the feature in the config file: 

```php
/*
| Specify if restify can override the default laravel exception handler and generate AI based solutions for exceptions.
| This feature requires you to have an OpenAI API key.
 */
'ai_solutions' => true,
```

This feature is only enabled when the `app.debug` is set to `true`.

Considering this feature is using the openai php package, you should also publish the config file of the [openai-php/laravel](https://github.com/openai-php/laravel#get-started) package:

```
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

and set the `OPENAI_API_KEY` in the `.env` file.

The open ai key can be obtained from [here](https://platform.openai.com/account/api-keys).


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
