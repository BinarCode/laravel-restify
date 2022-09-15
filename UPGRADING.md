# Upgrading

## From 7.3.1 to 7.4.0

## Breaking

- The `$eagerState` repository property is now private, and it is of type `null|string` because it holds the parent repository that renders it.

## From 6.x to 7.x

High impact:

- Any action permitted unless the Model Policy exists and the method is defined
- PHP8.0 is required
- Laravel 9 is required
- Repository.php:
    - static `to` method renamed to `route`
    - `$withs` class property was renamed to `$with` so it matches the Eloquent default
    - `$defaultPerPage` and `$defaultRelatablePerPage` has a type of `int`, if you override this make sure you add `int` type
    - `eagerState` method was deleted from the repository, there is no need to call it anymore, the repository will be resolved automatically
    - `$prefix` property requires a `string` type
    - `resolveShowMeta` is not inherited for the `resolveIndexMeta` anymore, both methods are now using `policyMeta` method, so override the `policyMeta` instead. This could be simply solved if you replace in all repositories `resolveShowMeta` with `policyMeta`.
- Relations that are present into `include` or `related` will be preloaded, so if you didn't specify a repository to serialize the related relationship, and you're looking for the Eloquent to resolve it, it will not invoke the `restify.casts.related` cast anymore, instead it'll load the relationship as it. This has a performance reason under the hood. 
- Since related relationships will be preloaded, the format of the belongs to will be changed now. If you didn't specify the repository to serialize the `belongsTo` relationship, it'll be serialized as an object, not array anymore:

Before:
```json
"relationships": {
  "user": [{
    "name": "Foo"
  }]
}
```

Now:
```json
"relationships": {
  "user": {
    "name": "Foo"
}
}
```

Low impact: 

- Restify.php - `repositoryForKey` renamed to `repositoryClassForKey`

## From 6.2.1 to 6.3.0

- The `src/Events/AddedRepositories.php` event was removed because of a [conflict with telescope](https://github.com/laravel/telescope/issues/1152).

## From 5.x to 6.x

### Filtering

- The major deprecation was the `AuthController` deletion, as it wasn't very intuitive and configurable. Intead we developed individual controllers for each auth action, you can release them using the `restify:auth` command. See more [on the official docs](https://restify.binarcode.com/auth/authentication#define-routes);
- Matchable are now only read from query params, not post payloads. So make sure all matchable filters are in query params.
- Actions are not logged if the model doesn't use HasActionLog trait.

### Fields

- A major breaking change was made around the `storeCallback`, `updateCallback` and `storeBulkCallback`. In 5.x the closure was receiving the `RestifyRequest $request` instance, however now, it only gets the value (so it's compatible with the `showCallback` or `indexCallback`): 
```php
field('name')->storeCallback(fn($value) => Str::upper($value)) // $value === $request->input('name')
```

How to fix: 

If you already implemented this callback, you still can use instead the `fillCallback`, so simply replace you `storeCallback` with `fillCallback`: 

```php
field('name')->fillCallback(function(RestifyRequest $request) {
    if ($request->isStoreRequest()) {
        return Str::upper($request->input('name'));
    }
});
```

## From 4.10.x to 5.x

### Repository changes:
- `filters` - explicit `array` returned type
- `getSearchableFields()` - deprecated, to use `searchables`
- `getMatchByFields()` - deprecated, to use `matches`
- `getOrderByFields()` - deprecated, to use `sorts`
- `availableFilters` now returns an instance of `FiltersCollection`, so if you overwrite this, make sure to adapt.
- `uriTo` - explicit `string` returned type
- The support for relatable via query params was dropped because of security reasons. Now we only maintain the relatable via `BelongsToMany` or `HasMany` fields. ie: `/posts?parentRepository=users&parentRepositoryId=1` should be now: `/users/1/posts` and define the `'posts' => HasMany::new('')...` into your `UserRepository` 
- Repository `index`, `show`, `store`, `update`, `destroy` should specify the `JsonResponse` return.
- `getRelated` method was dropped - use `related` instead
- `getMatchByFields` method was dropped - use `matches` instead
- `getOrderByFields` method was dropped - use `sorts` instead
- `getSearchableFields` method was dropped - use `searchables` instead
- `getWiths` method was dropped - use `withs` instead
### Filters

- `uriKey()` - `string` returned type
- `BooleanFilter` - changed namespace to `Binaryk\LaravelRestify\Filters`
- `SelectFilter` - changed namespace to `Binaryk\LaravelRestify\Filters\SelectFilter` and must implement the `options` method and return an array. The key should be the value the frontend can send and value would be the label frontend could use to display the select. ie: ['is_active' => 'Is Active']
- There is no more `class` property for the advanced filters, the frontend should only send they `key` of the filter.
- The `filters` method from the repository should return a list of `AdvancedFilers`.
  
- The `resolve` method for the advanced filters now requires a `AdvancedFilterPayloadDto` instance.
- The `value` argument for the third parameter for `AdvancedFilters` will be resolved from the `AdvancedFilterPayloadDto@value` method
- Each advanced filter must implement the `rules` method which returns the validation payload for the filter.

### Profile
- POST `/profile/avatar` was deleted.

### RestController

- RestController namespace was changed to `Binaryk\LaravelRestify\Http\Controllers` so you should refactor all of yours classes where you have used it (tip: you can use `data()` helper to wrap any response into json with `data` key)
- `Action` base class doesn't extend anymore the `RestController`.
- `RestifyHandler` was removed.

### Fields

- For all related fields (BelongsTo, HasMany etc.) was dropped the argument one, so instead of `BelongsTo::make('user', 'user', UserRepository::class)` you have to use: `BelongsTo::make('user', UserRepository::class)`

### Others

- `RestifyServiceProviderRegistered` event was removed


## From 4.7.0 to 4.8.0 

- Copy the `database/migrations/create_action_logs_table.php` migration to yours local migrations and run `php artisan migrate` to ensure you can benefit from the `action logs`.

## From v3 to v4

- Dropped support for laravel passport
- Now you have to explicitly define the `allowRestify` method in the model policy, by default Restify don't allow you to use repositories.
- `viewAny` policy isn't used anymore, you can delete it.
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

- The relations from the Repository `$related`, which are resolved via a `Illuminate\Database\Eloquent\Relations\Relation` or `Illuminate\Database\Eloquent\Builder` will do not have anymore the `attributes` property in the relations. To support this format, you can configure a custom Cast on `restify.php`, see the `Binaryk\LaravelRestify\Tests\Fixtures\Post\RelatedCastWithAttributes::class`, it returns the old 3.x format.
