# Upgrading

## From 9.x to 10.x

### New Features

#### Modern Model Definition with PHP Attributes

Laravel Restify v10 introduces a modern way to define models using PHP 8+ attributes. While your existing static property approach will continue to work, we recommend migrating to the new attribute-based syntax for better developer experience.

**Before (v9 and earlier):**
```php
class UserRepository extends Repository
{
    public static string $model = User::class;
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
        ];
    }
}
```

**After (v10 - Recommended):**
```php
use Binaryk\LaravelRestify\Attributes\Model;

#[Model(User::class)]
class UserRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
        ];
    }
}
```

**Benefits of migrating to attributes:**
- 🎯 **Modern, declarative approach** - More intuitive and cleaner code
- 🔍 **Better IDE support** - Enhanced autocompletion and static analysis
- 📦 **Type-safe** - Use `::class` syntax for better refactoring support
- 🔧 **More discoverable** - Attributes are easier to find with reflection tools
- 🚀 **Future-proof** - Follows modern PHP practices

**Migration Strategy:**

1. **No immediate action required** - All existing repositories continue to work as-is
2. **Gradual migration** - Update repositories one at a time when convenient
3. **Mixed approach** - You can use both attributes and static properties in the same codebase

**Priority order for model resolution:**
1. `#[Model]` attribute (highest priority)
2. `public static string $model` property
3. Auto-guessing from repository class name (lowest priority)

This change is **100% backward compatible** - no existing code will break.

#### Improved Field-Level Search and Sorting

Laravel Restify v10 introduces a more intuitive way to define searchable and sortable fields directly on the field definitions. While the static array approach continues to work, the new field-level methods provide better organization and discoverability.

**Before (v9 and earlier):**
```php
class UserRepository extends Repository
{
    public static array $search = ['name', 'email'];
    public static array $sort = ['name', 'email', 'created_at'];
    
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name'),
            field('email'),
            field('created_at'),
        ];
    }
}
```

**After (v10 - Recommended):**
```php
#[Model(User::class)]
class UserRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        return [
            field('name')->searchable()->sortable(),
            field('email')->searchable()->sortable(),
            field('created_at')->sortable(),
        ];
    }
}
```

**Benefits of field-level configuration:**
- 📍 **Co-located configuration** - Search/sort behavior defined alongside the field
- 🔍 **Better discoverability** - Easy to see which fields are searchable/sortable at a glance  
- 🎛️ **More granular control** - Configure search and sort behavior per field
- 🧹 **Cleaner repositories** - Reduces static array properties
- 💡 **IDE-friendly** - Better autocompletion and method chaining

**Migration Strategy:**

1. **Static arrays still work** - No need to change existing repositories immediately
2. **Field-level takes precedence** - If both are defined, field-level configuration wins
3. **Gradual migration** - Update fields one at a time or per repository
4. **Mixed approach** - You can use both approaches in the same codebase during transition

**Priority order for search/sort resolution:**
1. Field-level `->searchable()`/`->sortable()` methods (highest priority)
2. Static `$search`/`$sort` arrays (fallback)

This change is also **100% backward compatible** - existing static arrays continue to work perfectly.

## Breaking Changes

### Default Search Behavior Change

🚨 **Breaking Change**: In version 10, repositories no longer search by the model's primary key (ID) by default when no searchable fields are defined.

**Before (v9 and earlier):**
```php
class UserRepository extends Repository
{
    // No $search property defined
    // Automatically searched by 'id' field by default
}
```

**After (v10):**
```php
class UserRepository extends Repository
{
    // No $search property defined
    // No searchable fields available - search returns empty results
}
```

**To maintain the previous behavior**, add this method to your Repository parent class or individual repositories:

```php
public static function searchables(): array
{
    return empty(static::$search)
        ? [static::newModel()->getKeyName()]
        : static::$search;
}
```

**Why this change was made:**
- **Security**: Prevents unintended ID-based searches on sensitive repositories
- **Explicit configuration**: Forces developers to explicitly define searchable fields
- **Performance**: Avoids unnecessary database queries when search isn't intended
- **Consistency**: Aligns with the principle of explicit over implicit behavior

**Migration strategy:**
1. **Immediate fix**: Add the `searchables()` method to your base Repository class to restore v9 behavior globally
2. **Recommended approach**: Review each repository and explicitly define `$search` arrays with appropriate fields
3. **Security review**: Consider which repositories should actually be searchable and by which fields

### Configuration File Updates

When upgrading to v10, it's important to ensure your local `config/restify.php` file includes all the new configuration options that have been added.

**Recommended Steps:**

1. **Compare configuration files** - Check your local `config/restify.php` against the latest version
2. **Review new sections** - Look for new configuration options that may have been added
3. **Merge changes** - Add any missing configuration sections to your local file

**New configuration sections in v10 may include:**

```php
// Example new sections (check the actual config file for current options)
'mcp' => [
    'tools' => [
        'exclude' => [],
        'include' => [],
    ],
    'resources' => [
        'exclude' => [],
        'include' => [],
    ],
    'prompts' => [
        'exclude' => [],
        'include' => [],
    ],
],

'ai_solutions' => [
    'model' => 'gpt-4.1-mini',
    'max_tokens' => 1000,
],
```

**How to update your config:**

1. **Backup your current config** - Copy your existing `config/restify.php`
2. **Republish the config** (optional):
   ```bash
   php artisan vendor:publish --provider="Binaryk\LaravelRestify\LaravelRestifyServiceProvider" --tag="config" --force
   ```
3. **Merge your custom settings** - Copy your custom values back into the new config file
4. **Test your application** - Ensure all functionality works as expected

<alert type="warning">
Always backup your existing configuration before making changes, especially if you have custom settings.
</alert>

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
