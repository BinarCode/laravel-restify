# Upgrading

Because there are many breaking changes an upgrade is not that easy. There are many edge cases this guide does not cover. We accept PRs to improve this guide.

## From v3 to v4

- Dropped support for laravel passport
- Now you have to explicitly define the `allowRestify` method in the model policy, by default Restify don't allow you to use repositories.
- `viewAny` policy is not used anymore, you can delete it.
- The default exception handler is the Laravel one, see `restify.php -> handler`
- `fillCallback` signature has changed
- By default it will do not allow you to attach `belongsToMany` and `morphToMany` relationships. You will have to add `BelongsToMany` or `MorphToMany` field into your repository
- All of the `Repository` getter methods should declare the returned type, for instance the `fieldsForIndex` method should say that it returns an `:array` 
- Attach endpoint:
```php
"api/restify/users/{$user->id}/attach/roles", [
    'roles' => [$role->id],
]
```
now requires to have a `Binaryk\LaravelRestify\Fields\BelongsToMany` or `Binaryk\LaravelRestify\Fields\MorphToMany` field to be defined in the repository.

- Field method `append` renamed to `value`.
