---
title: Fields 
menuTitle: Fields 
category: API 
position: 8
---

A field is basically the model's attribute representation.

## Declaration

Each Field generally extends the `Binaryk\LaravelRestify\Fields\Field` class from the Laravel Restify. This class ships
a fluent API for a variety of mutators, interceptors and validators.

To add a field to a repository, we can simply add it to the repository's fields method. Typically, fields may be created
using their static `new` or `make` method. 

The first argument is always the attribute name and usually matches the database `column`.

```php

use Illuminate\Support\Facades\Hash;
use Binaryk\LaravelRestify\Fields\Field;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

public function fields(RestifyRequest $request)
{
    return [
        Field::make('name')->required(),
        
        Field::make('email')->required()->storingRules('unique:users')->messages([
            'required' => 'This field is required.',
        ]),
    ];
}
```

### `field` helper

<alert>

Instead of using the `Field` class, you can use the `field` helper. For example:

```php 
field('email')
```

</alert>

### Computed field

The second optional argument is a callback or invokable, and it represents the displayable value of the field either in `show` or `index` requests. 

```php
field('name', fn() => 'John Doe')
```

The field above will always return the `name` value as `John Doe`. The field is still writeable, so you can update or create an entity by using it.

### Readonly field

If you don't want a field to be writeable you can mark it as readonly: 

```php
field('title')->readonly()
```

The `readonly` accepts a request as well as you can use: 

```php
field('title')->readonly(fn($request) => $request->user()->isGuest())
```

### Virtual field

A virtual field, is a field that's [computed](#computed-field) and [readonly](#readonly-field).

```php
field('name', fn() => "$this->first_name $this->last_name")->readonly()
```


## Authorization

The `Field` class provides a few methods in order to authorize certain actions. Each authorization method accepts a `Closure` that
should return `true`
or `false`. The `Closure` will receive the incoming `\Illuminate\Http\Request` request.

### Can see

Sometimes, you may want to hide certain fields from a group of users. You may easily accomplish this by chaining
the `canSee`:

 ```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canSee(fn($request) => $request->user()->isAdmin())
    ];
}
```

### Can store

The can store closure:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canStore(fn($request) => $request->user()->isAdmin())
}
```

### Can update

The can update closure:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('role_id')->canUpdate(fn($request) => $request->user()->isAdmin())
    ];
}
```

### Can patch

You can authorize PATCH operations specifically:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('status')->canPatch(fn($request) => $request->user()->can('patch-status'))
    ];
}
```

### Can update bulk

For bulk update operations, you can control authorization:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('priority')->canUpdateBulk(fn($request) => $request->user()->isAdmin())
    ];
}
```

## Bulk Operations

Laravel Restify provides specialized methods for handling bulk operations (creating or updating multiple records at once). Fields have specific callbacks and validation rules for these scenarios.

### Bulk Visibility Control

You can control whether fields are visible during bulk operations:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('title')->showOnStoreBulk(true)->showOnUpdateBulk(false),
        field('slug')->hideFromStoreBulk(), // Not editable during bulk creation
    ];
}
```

The available visibility methods for bulk operations are:
- `isShownOnStore()` - Check if field is shown during single store
- `isShownOnStoreBulk()` - Check if field is shown during bulk store  
- `isShownOnUpdate()` - Check if field is shown during single update
- `isShownOnUpdateBulk()` - Check if field is shown during bulk update

### Bulk Authorization

Use specialized authorization methods for bulk operations:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('status')
            ->canStore(fn($request) => $request->user()->isAdmin())
            ->canUpdateBulk(fn($request) => $request->user()->isSuperAdmin()),
    ];
}
```

## Field Type Detection

Restify includes an intelligent field type detection system that automatically infers the appropriate data type for fields based on various factors. This is particularly useful for API schema generation and MCP integration.

### Automatic Type Detection

The `guessFieldType()` method analyzes fields using multiple strategies:

```php
$field = field('email')->rules('required', 'email');
$type = $field->guessFieldType(); // Returns: 'string'

$field = field('is_active')->rules('boolean'); 
$type = $field->guessFieldType(); // Returns: 'boolean'

$field = field('age')->rules('integer', 'min:0');
$type = $field->guessFieldType(); // Returns: 'number'
```

### Detection Strategies

The system uses three detection strategies in order of priority:

1. **Field Class Detection** - Analyzes the field class name (File, Image, Boolean, etc.)
2. **Validation Rules Detection** - Examines validation rules (email, boolean, integer, etc.)  
3. **Attribute Name Patterns** - Looks for common naming patterns

#### Field Class Patterns

```php
File::make('avatar')->guessFieldType(); // 'string'
Image::make('photo')->guessFieldType(); // 'string'  
BooleanField::make('active')->guessFieldType(); // 'boolean'
```

#### Validation Rule Patterns

```php
field('email')->rules('email')->guessFieldType(); // 'string'
field('count')->rules('integer')->guessFieldType(); // 'number'
field('tags')->rules('array')->guessFieldType(); // 'array' 
```

#### Attribute Name Patterns

```php
field('is_featured')->guessFieldType(); // 'boolean' (is_ prefix)
field('user_id')->guessFieldType(); // 'number' (_id suffix)
field('created_at')->guessFieldType(); // 'string' (_at suffix)
field('settings_json')->guessFieldType(); // 'array' (_json suffix)
```

### Computed Field Detection

You can check if a field is computed (virtual/calculated):

```php
$field = field('full_name', fn() => "$this->first_name $this->last_name");
$isComputed = $field->computed(); // Returns: true
```

## Sorting

Fields can be made sortable, allowing API consumers to order results by field values.

### Making Fields Sortable

To make a field sortable, chain the `sortable()` method:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('name')->sortable(),
        field('email')->sortable(),
        field('created_at')->sortable(),
        field('is_active')->sortable(),
    ];
}
```

### Sortable Column Configuration

By default, the field's attribute name is used as the sortable column. You can specify a different column:

```php
field('full_name')->sortable('name'), // Use 'name' column for 'full_name' field
```

### Disabling Sorting

You can disable sorting for a field that was previously made sortable:

```php
field('sensitive_data')->sortable(false),
```

### Conditional Sorting

Make fields conditionally sortable based on request context:

```php
field('internal_score')->sortable(fn($request) => $request->user()->isAdmin()),
```

### Using Sortable Fields

Once fields are marked as sortable, API consumers can use them in sort requests:

```http
GET /api/restify/users?sort=name
GET /api/restify/users?sort=-created_at  # Descending
GET /api/restify/users?sort=name,-created_at  # Multiple fields
```

## Matching

Fields can be made matchable, allowing API consumers to filter results using query parameters.

### Making Fields Matchable

Use the `matchable()` method or convenient aliases:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('name')->matchableText(),              // Text matching with LIKE
        field('email')->matchable('users.email'),   // Custom column - users table email
        field('status')->matchableText(),            // Text matching
        field('is_active')->matchableBool(),         // Boolean matching
        field('age')->matchableInteger(),            // Integer matching
        field('created_at')->matchableDatetime(),    // Date matching
        field('price')->matchableBetween(),          // Range matching
        field('tags')->matchableArray(),             // Array/IN matching
    ];
}
```

### Using Matchable Fields

Once fields are marked as matchable, API consumers can filter using query parameters:

```http
GET /api/restify/posts?title=Laravel         # Text matching
GET /api/restify/posts?is_active=true        # Boolean matching
GET /api/restify/posts?user_id=5             # Integer matching
GET /api/restify/posts?created_at=2023-12-01 # Date matching
GET /api/restify/posts?price=100,500         # Range matching
GET /api/restify/posts?tags=php,laravel      # Array matching

# Negation (prefix with -)
GET /api/restify/posts?-status=draft         # Exclude drafts
GET /api/restify/posts?-is_active=true       # Inactive posts

# Null checks
GET /api/restify/posts?description=null      # Posts with no description
```

### Match Types Reference

| Alias | Type | Example Usage | Query Behavior |
|-------|------|---------------|----------------|
| `matchableText()` | text | `?name=john` | `WHERE name LIKE '%john%'` |
| `matchableBool()` | boolean | `?is_active=true` | `WHERE is_active = 1` |
| `matchableInteger()` | integer | `?user_id=5` | `WHERE user_id = 5` |
| `matchableDatetime()` | datetime | `?created_at=2023-12-01` | `WHERE DATE(created_at) = '2023-12-01'` |
| `matchableBetween()` | between | `?price=100,500` | `WHERE price BETWEEN 100 AND 500` |
| `matchableArray()` | array | `?tags=php,laravel` | `WHERE tags IN ('php', 'laravel')` |

### Advanced Matchable Configuration

The `matchable()` method is flexible and accepts multiple types of arguments for advanced filtering scenarios:

#### Basic Usage (No Arguments)

When called without arguments, `matchable()` enables text-based matching using the field's attribute name:

```php
field('title')->matchable(), // Enables text matching on 'title' column
```

#### Custom Column

Specify a different database column for matching:

```php
field('display_name')->matchable('users.name'), // Match against 'users.name' column
```

#### Custom Match Type

Specify both column and match type:

```php
field('status')->matchable('posts.status', 'text'), // Custom column with text matching
field('priority')->matchable('priority', 'integer'), // Integer matching
```

#### Closure-based Matching

For complex filtering logic, pass a closure that receives the request, query builder, and value:

```php
field('title')->matchable(function ($request, $query, $value) {
    // Custom search logic - case insensitive partial matching
    $query->where('title', 'like', "%{$value}%");
}),

field('content')->matchable(function ($request, $query, $value) {
    // Full-text search across multiple columns
    $query->whereRaw("MATCH(title, content) AGAINST(? IN BOOLEAN MODE)", [$value]);
}),

field('location')->matchable(function ($request, $query, $value) {
    // Complex geographical search
    [$lat, $lng, $radius] = explode(',', $value);
    $query->whereRaw(
        'ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?)) <= ?',
        [$lng, $lat, $radius * 1000]
    );
}),
```

#### Custom MatchFilter Classes

For reusable complex filtering logic, create custom MatchFilter classes:

```php
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class CustomTitleFilter extends MatchFilter
{
    public function __construct()
    {
        parent::__construct();
        $this->setColumn('title'); // Set the column to filter on
    }

    public function filter(RestifyRequest $request, Builder|Relation $query, $value)
    {
        // Custom filtering logic: search for titles that start with the given value
        $query->where('title', 'like', "{$value}%");
        
        return $query;
    }
}
```

Then use the custom filter in your field definition:

```php
field('title')->matchable(new CustomTitleFilter()),
```

#### Invokable Classes

You can also use invokable classes for cleaner code organization:

```php
class SearchTitleFilter
{
    public function __invoke($request, $query, $value)
    {
        $query->where('title', 'like', "%{$value}%")
              ->orWhere('description', 'like', "%{$value}%");
    }
}

// Usage
field('search')->matchable(new SearchTitleFilter()),
```

#### Practical Examples

**E-commerce Product Search:**
```php
field('search')->matchable(function ($request, $query, $value) {
    $query->where(function ($q) use ($value) {
        $q->where('name', 'like', "%{$value}%")
          ->orWhere('description', 'like', "%{$value}%")
          ->orWhere('sku', 'like', "%{$value}%");
    });
}),
```

**Date Range Filtering:**
```php
field('date_range')->matchable(function ($request, $query, $value) {
    [$start, $end] = explode(',', $value);
    $query->whereBetween('created_at', [$start, $end]);
}),
```

**Tag-based Filtering:**
```php
field('tags')->matchable(function ($request, $query, $value) {
    $tags = explode(',', $value);
    $query->whereHas('tags', function ($q) use ($tags) {
        $q->whereIn('slug', $tags);
    });
}),
```

**Relationship Filtering:**
```php
field('author')->matchable(function ($request, $query, $value) {
    $query->whereHas('author', function ($q) use ($value) {
        $q->where('name', 'like', "%{$value}%")
          ->orWhere('email', 'like', "%{$value}%");
    });
}),
```

## Searchable

Fields can be made searchable, enabling them to respond to global search queries. This provides field-level control over search behavior while maintaining the simplicity of the global search API.

### Making Fields Searchable

To make a field searchable, chain the `searchable()` method:

```php
public function fields(RestifyRequest $request)
{
    return [
        field('title')->searchable(),
        field('description')->searchable(),
        field('email')->searchable(),
    ];
}
```

The `searchable()` method uses a unified flexible signature that accepts multiple arguments and works consistently across all field types:

```php
// Basic usage
field('title')->searchable(),

// Custom column
field('name')->searchable('users.full_name'),

// With optional type
field('price')->searchable('products.price', 'numeric'),

// Multiple attributes (especially useful for relationship fields like BelongsTo)
BelongsTo::make('author')->searchable('name', 'email', 'username'),

// Array of attributes (legacy support)
BelongsTo::make('editor')->searchable(['users.name', 'users.email']),

// Closure/callback
field('content')->searchable(function ($request, $query, $value) {
    // Custom search logic
}),

// Custom filter instance
field('complex_search')->searchable(new CustomSearchFilter()),

// Invokable class
field('tags')->searchable(new TagSearchHandler()),
```

### Unified Method Signatures

All searchable-related methods now use consistent signatures across regular fields and relationship fields:

```php
// All field types use the same signatures:
searchable(...$attributes)                    // Flexible variadic signature
isSearchable(?RestifyRequest $request = null) // Optional request parameter
getSearchColumn(?RestifyRequest $request = null) // Optional request parameter

// BelongsTo also provides relationship-specific method:
getSearchables(): array                       // Returns multiple searchable attributes
```

### Using Searchable Fields

Searchable fields respond to the standard `search` query parameter:

```http
GET /api/restify/posts?search=laravel
```

This will search across all searchable fields for the term "laravel".

### Advanced Searchable Configuration

#### Basic Usage (No Arguments)

When called without arguments, `searchable()` applies standard search behavior using the field's attribute:

```php
field('title')->searchable(), // Searches the 'title' column with LIKE operator
```

#### Custom Column

Specify a different database column for searching:

```php
field('author_name')->searchable('users.name'), // Search in users.name column
```

You can also specify multiple attributes for relationship fields (like BelongsTo):

```php
BelongsTo::make('author', UserRepository::class)->searchable('name', 'email'),
```

#### Closure-based Searching

For custom search logic, pass a closure that receives the request, query builder, and search value:

```php
field('content')->searchable(function ($request, $query, $value) {
    $query->where('title', 'LIKE', "%{$value}%")
          ->orWhere('description', 'LIKE', "%{$value}%");
}),
```

#### Custom SearchableFilter Classes

Create dedicated filter classes for complex search logic:

```php
field('complex_search')->searchable(new CustomContentSearchFilter),
```

Where `CustomContentSearchFilter` extends `SearchableFilter`:

```php
use Binaryk\LaravelRestify\Filters\SearchableFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class CustomContentSearchFilter extends SearchableFilter
{
    public function filter(RestifyRequest $request, $query, $value)
    {
        return $query->where(function ($q) use ($value) {
            $q->where('title', 'LIKE', "%{$value}%")
              ->orWhere('description', 'LIKE', "%{$value}%")
              ->orWhere('tags', 'LIKE', "%{$value}%");
        });
    }
}
```

#### Invokable Classes

For reusable search logic, use invokable classes:

```php
field('tags')->searchable(new TagSearchFilter),
```

```php
class TagSearchFilter
{
    public function __invoke($request, $query, $value)
    {
        $tags = explode(',', $value);
        $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', $tags);
        });
    }
}
```

#### Practical Examples

**Full-text Search:**
```php
field('content')->searchable(function ($request, $query, $value) {
    $query->whereFullText(['title', 'description'], $value);
}),
```

**Multi-field Search:**
```php
field('user_search')->searchable(function ($request, $query, $value) {
    $query->where('name', 'LIKE', "%{$value}%")
          ->orWhere('email', 'LIKE', "%{$value}%")
          ->orWhere('phone', 'LIKE', "%{$value}%");
}),
```

**Relationship Search:**
```php
field('author')->searchable(function ($request, $query, $value) {
    $query->whereHas('author', function ($q) use ($value) {
        $q->where('name', 'like', "%{$value}%");
    });
}),
```

**JSON Search:**
```php
field('metadata')->searchable(function ($request, $query, $value) {
    $query->whereJsonContains('metadata->tags', $value);
}),
```

## Validation

There is a golden rule that says - catch the exception as soon as possible on its request way.

Validations are the first bridge of your request information, so it would be a good start to validate your input. In this manner, you
don't have to worry about the payload anymore.

### Attaching rules

Validation rules could be added by chaining the `rules` method to
attach [validation rules](https://laravel.com/docs/validation#available-validation-rules) to the field:

```php
field('email')->rules('required', 'email'),
```

Of course, if you are leveraging Laravel's support
for [validation rule objects](https://laravel.com/docs/validation#using-rule-objects), you may attach those to the resources
as well:

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

<alert type="success">

Considering the `required` rule is very often used, Restify provides a `required()` validation
helper: `field('email')->required()`

</alert>

These rules will be applied for all the update and store requests.

### Storing Rules

If you would like to define more specific rules that only apply when a resource is being stored, you might want to use
the `storingRules` method:

```php
Field::new('email')
    ->rules('required', 'email', 'max:255')
    ->storingRules('unique:users,email');
```

Considering the fact that Restify concatenates rules provided by the `rules()` method, the entire validation for a POST request on
this repository will look like this:

```php
$request->validate([
    'email' => ['required', 'email', 'max:255', 'unique:users,email']
]);
```

### Updating Rules

Similarly, if you would like to define rules that only apply when a resource is being updated, you may use
the `updatingRules` method.

```php
Field::new('email')->updatingRules('required', 'email');
```

### Bulk Rules

For bulk operations, you can specify validation rules that apply only during bulk store or bulk update operations:

#### Store Bulk Rules

Rules that apply only during bulk store operations:

```php
Field::new('email')
    ->rules('required', 'email')
    ->storeBulkRules('unique:users,email');
```

#### Update Bulk Rules

Rules that apply only during bulk update operations:

```php
Field::new('email')
    ->rules('email')
    ->updateBulkRules('required');
```

### Store Rules Alias

You can also use `storeRules()` as an alias for `storingRules()`:

```php
Field::new('email')->storeRules('unique:users,email');
```

## Interceptors

Sometimes you might want to take control over certain Field actions. 

That's why the Field class exposes a lot of chained methods you can call to configure it.

### Fill callback

During the `store` and `update` requests, there are two steps before the value from the Request is attached to the model attribute. 

First, it is retrieved from the application request and passed to the `fillCallback`. Then, the value is passed through the `storeCallback` or `updateCallback`:

You may intercept each of these with closures.

Let's start with the `fillCallback`. It accepts a `callable` (an invokable class) or a Closure. The callable will receive the Request, the repository model (an empty one for storing and filled one for updating) and the attribute name:

```php
field('title')->fillCallback(function (RestifyRequest $request, Post $model, $attribute) {
    $model->title = strtoupper($request->input('title_from_the_request'));
})
```

This way you can get anything from the `$request` and perform any transformations with the value before storing.

### Store callback

Another handy interceptor is the `storeCallback`. This is the step that comes immediately before attaching the value from the
request to the model attribute:

This interceptor may be useful for modifying the value passed through the `$request`.

```php
Field::new('password')->storeCallback(function (RestifyRequest $request) {
    return Hash::make($request->input('password'));
});
```

### Update callback

The `updateCallback` works in the same manner. Let's use an invokable this time:

```php
Field::new('password')->updateCallback(new PasswordUpdateInvokable);
```

Where the `PasswordUpdateInvokable` could be an invokable class: 

```php
class PasswordUpdateInvokable 
{
    public function __invoke(Request $request)
    {
        return Hash::make($request->input('password'));
    }
}
```

### Store bulk callback

For bulk store operations, you can use the `storeBulkCallback` to modify values during bulk creation:

```php
Field::new('slug')->storeBulkCallback(function (RestifyRequest $request) {
    return Str::slug($request->input('title'));
});
```

### Index Callback

Sometimes, you might want to transform an attribute from the database right before it is returned to the frontend.

Transform the value for the following index request:

```php
Field::new('password')->indexCallback(function ($value) {
    return Hash::make($value);
});
```

### Show callback

Transform the value for the following show request:

```php
Field::new('password')->showCallback(function ($value) {
    return Hash::make($value);
});
```

### Resolve callback

Transform the value for both `show` and `index` requests:

```php
Field::new('password')->resolveCallback(function ($value) {
    return Hash::make($value);
});
```

### Fields actionable

At times, storing attributes might require the stored model before saving it. 

For example, let's say the Post model uses the [media library](https://spatie.be/docs/laravel-medialibrary/v9/introduction), and has the `media` relationship that is a list of Media files:

```php
// PostRepository

public function fields(RestifyRequest $request): array
{
    return [
        field('title'),
        
        field('files', 
            fn () => $this->model()->media()->pluck('file_name')
        )
        ->action(new AttachPostFileRestifyAction),
    ];
}
```

So we have a virtual `files` field (it's not an actual database column) that uses a [computed field](#computed-field) to display the list of Post's files names. The `->action()` calls and accepts an instance of a class that extends `Binaryk\LaravelRestify\Actions\Action`: 

```php
class AttachPostFileRestifyAction extends Action
{
    public function handle(RestifyRequest $request, Post $post): void
    {
        $post->addMediaFromRequest('file')
            ->toMediaCollection();
    }
}
```

The action gets the `$request` and the current `$post` model. Let's say the frontend has to create a post with a file:

```javascript
const data = new FormData;
data.append('file', blobFile);
data.append('title', 'Post title');

axios.post(`api/restify/posts`, data);
```

We were able to create the post and attach a file using media library in a single request. Otherwise, it would have implied creating 2 separate requests (post creation and file attaching).

Actionable fields handle [store](/repositories#store-request), put, [bulk store](/repositories#store-bulk-flow) and bulk update requests.

## Fallbacks

### Default Stored Value

Usually, there is necessary to store a field as `Auth::id()`. This field will be automatically populated by Restify if
you specify the `value` value for it:

```php
Field::new('user_id')->value(Auth::id());
```

or by using a closure:

```php
Field::new('user_id')->hidden()->value(function(RestifyRequest $request, $model, $attribute) {
    return $request->user()->id;
});
```

### Default Displayed Value

If you have a field which has `null` value into the database, you might want to return a fallback default value for
the frontend:

```php
Field::new('description')->default('N/A');
```

Now, for the fields that don't have a description into the database, it will return `N/A`.

<alert type="info">
The default value is ONLY used for the READ, not for WRITE requests.
</alert>

### Default Stored Value

During any (update or store requests), this is called after the fill and store callbacks.

You can pass a callable or a value, and it will be attached to the model if no value provided otherwise.

Imagine it's like `attributes` in the model:

```php
field('currency')->defaultCallback('EUR'),
```

## Customizations

### Field label

Field label, so you can replace a field attribute spelling when it is returned to the frontend:

```
Field::new('created_at')->label('sent_at')
```

If you want to populate this value from a frontend request, you can use the label as a payload key.

### Hidden field

Field can be setup as hidden:

```php
Field::new('token')->hidden(); // this will not be visible 
```

However, you can populate the field value when the entity is stored by using `value`:

```php
Field::new('token')->value(Str::random(32))->hidden();
```

### MCP Visibility Control

When using Laravel Restify with Model Context Protocol (MCP), you can control field visibility specifically for MCP requests using dedicated methods:

```php
// Hide field from MCP requests completely
Field::new('secret_key')->hideFromMcp()

// Show field only in MCP requests (hide from regular API)
Field::new('mcp_metadata')->showOnIndex(false)->showOnShow(false)->showOnMcp(true)

// Conditionally hide based on user permissions
Field::new('admin_notes')->hideFromMcp(function($request, $repository) {
    return !$request->user()->isAdmin();
})

// Show field in MCP based on user role
Field::new('sensitive_data')->showOnMcp(function($request, $repository) {
    return $request->user()->can('view-sensitive', $repository);
})
```

#### MCP Visibility Methods

- **`showOnMcp($callback = true)`** - Control whether the field should be visible in MCP requests
- **`hideFromMcp($callback = true)`** - Hide the field from MCP requests (inverse of showOnMcp)

Both methods accept either a boolean value or a callback function that receives the request and repository as parameters.

<alert type="info">
MCP visibility rules take precedence over regular `showOnIndex`/`showOnShow` rules when processing MCP requests. Fields are visible in MCP by default unless explicitly hidden.
</alert>

#### How It Works

The MCP visibility system automatically detects when a request is coming from an MCP tool and applies the appropriate visibility rules:

1. **Regular API requests** use `showOnIndex()` and `showOnShow()` rules
2. **MCP requests** use `showOnMcp()` and `hideFromMcp()` rules
3. **Default behavior** - fields are visible in MCP unless explicitly hidden

This allows you to have different field visibility for your regular API consumers versus AI agents accessing your data through MCP tools.

### Custom Tool Schema

When using MCP, you can define custom schema definitions for individual fields using the `toolSchema()` method:

```php
Field::new('status')->toolSchema(function ($field, $request, $repository) {
    return [
        'type' => 'string',
        'enum' => ['draft', 'published', 'archived'],
        'description' => 'The publication status of the content'
    ];
});

Field::new('settings')->toolSchema(function ($field, $request, $repository) {
    return [
        'type' => 'object',
        'properties' => [
            'theme' => ['type' => 'string'],
            'notifications' => ['type' => 'boolean']
        ],
        'description' => 'User configuration settings'
    ];
});
```

The `toolSchema()` callback receives:
- `$field` - The field instance
- `$request` - The current request
- `$repository` - The parent repository

This allows you to provide detailed schema information that helps MCP tools understand the structure and constraints of your data fields.

## Hooks

### After store

You can handle the after field store callback:

```php
Field::new('title')->afterStore(function($value) {
    dump($value);
})
```

### After update

You can handle the after field is updated callback:

```php
Field::new('title')->afterUpdate(function($value, $oldValue) {
    dump($value, $oldValue);
})
```

## File fields

To illustrate the behavior of Restify file upload fields, let's assume our application's users can upload "avatar
photos" to their account. Our users' database table will have an `avatar` column. This column will contain the path
to the profile on disk, or, when using a cloud storage provider such as Amazon S3, the profile photo's path within its
bucket.

### Defining the field

Next, let's attach the file field to our `UserRepository`. In this example, we will create the field and instruct it to
store the underlying file on the `public` disk. This disk name should correspond to a disk name in your `filesystems`
configuration file:

```php
use Binaryk\LaravelRestify\Fields\File;

public function fields(RestifyRequest $request)
{
    return [
        File::make('avatar')->disk('public')
    ];
}
```

<alert type="info">

You can use `field('avatar')->file()` instead of `File::make('avatar')` as well.

</alert>

### How Files Are Stored

When a file is uploaded by using this field, Restify will use
Laravel's [Filesystem integration](https://laravel.com/docs/filesystem) to store the file from the disk of your choice
with a randomly generated filename. Once the file is stored, Restify will store the relative path to the file in the
file field's underlying database column.

### URL Input Support

File fields also accept URL strings as input, providing flexibility when working with remote files or existing URLs:

```php
// You can send either a file upload or a URL string
POST /api/restify/users
{
    "name": "John Doe",
    "avatar": "https://example.com/images/avatar.jpg"
}

// Or upload a file traditionally
POST /api/restify/users
Content-Type: multipart/form-data
name: John Doe
avatar: [binary file data]
```

When a valid URL is provided:
- The URL is stored directly in the database column
- If `storeOriginalName()` is configured, the filename from the URL is extracted and stored
- Validation rules are automatically adjusted to accept both files and URLs

To illustrate the default behavior of the `File` field, let's take a look at an equivalent route that would store the
file in the same way:

```php
use Illuminate\Http\Request;

Route::post('/avatar', function (Request $request) {
    $path = $request->avatar->store('/', 'public');

    $request->user()->update([
        'avatar' => $path,
    ]);
});
```

If you are using the `public` disk with the `local` driver, you should run the `php artisan storage:link` Artisan
command to create a symbolic link from `public/storage` to `storage/app/public`. To learn more about file storage in
Laravel, check out the [Laravel file storage documentation](https://laravel.com/docs/filesystem).

### Image

The `Image` field behaves exactly like the `File` field; however, it will instruct Restify to only accept mimetypes of
type `image/*` for it:

```php
Image::make('avatar')->storeAs('avatar.jpg')
```

### Storing Metadata

In addition to storing the path to the file within the storage system, you may also instruct Restify to store the
original client filename and its size (in bytes). You may accomplish this using the `storeOriginalName` and `storeSize`
methods. Each of these methods accepts the name of the column that you would want to store the file's information in:

```php
Image::make('avatar')
    ->storeOriginalName('avatar_original')
    ->storeSize('avatar_size')
    ->storeAs('avatar.jpg')
```

The image above will store the file with the name `avatar.jpg` in the `avatar` column, the original file name
into `avatar_original` column and file size in bytes under `avatar_size` column (only if these columns are fillable on
your model).

<alert type="info">

You can use `field('avatar')->image()` instead of `Image::make('avatar')` as well.

</alert>

### Pruning & Deletion

File fields are deletable by default, so check out the following field definition:

```php
File::make('avatar')
```

You have a request to delete the avatar of the user with the id 1:

```http request
DELETE: api/restify/users/1/field/avatar
```

You can override this behavior by using the `deletable` method:

```php
File::make('Photo')->disk('public')->deletable(false)
```

Now, the field will not be deletable anymore.

### Customizing File Storage

Previously we learned that, by default, Restify stores the file using the `store` method of
the `Illuminate\Http\UploadedFile` class. However, you may fully customize this behavior based on your application's
needs.

#### Customizing The Name / Path

If you only need to customize the name or path of the stored file on disk, you may use the `path` and `storeAs` methods
of the `File` field:

```php
use Illuminate\Http\Request;

File::make('avatar')
    ->disk('s3')
    ->path($request->user()->id.'-attachments')
    ->storeAs(function (Request $request) {
        return sha1($request->attachment->getClientOriginalName());
    }),
```

#### Customizing The Entire Storage Process

However, if you would like to take **full** control over the file storage logic of a field, you may use the `store`
method. The `store` method accepts a callable which receives the incoming HTTP request and the model's instance associated
with the request:

```php
use Illuminate\Http\Request;

File::make('avatar')
    ->store(function (Request $request, $model) {
        return [
            'attachment' => $request->attachment->store('/', 's3'),
            'attachment_name' => $request->attachment->getClientOriginalName(),
            'attachment_size' => $request->attachment->getSize(),
        ];
    }),
```

As you can see in the example above, the `store` callback is returning an array of keys and values. These key / value
pairs are mapped onto your model's instance before it is saved to the database, allowing you to update one or many of the
model's database columns after your file is stored.

### Customizing File Display

By default, Restify will display the file's stored path name. However, you may customize this behavior.

#### Displaying temporary url

For disks such as S3, you may instruct Restify to display a temporary URL to the file instead of the stored path name:

```php
  field('path')
      ->file()
      ->path("documents/".Auth::id())
      ->resolveUsingTemporaryUrl()
      ->disk('s3'),

```

The `resolveUsingTemporaryUrl` accepts 3 arguments:


- `$resolveTemporaryUrl` - a boolean to determine if the temporary url should be resolved. Defaults to `true`.

- `$expiration` - A CarbonInterface to determine the time before the URL expires. Defaults to 5 minutes.

- `$options` - An array of options to pass to the `temporaryUrl` method of the `Illuminate\Contracts\Filesystem\Filesystem` implementation. Defaults to an empty array.

#### Displaying full url

For disks such as `public`, you may instruct Restify to display a full URL to the file instead of the stored path name:

```php
  field('path')
      ->file()
      ->path("documents/".Auth::id())
      ->resolveUsingFullUrl()
      ->disk('public'),

```

#### Storeables

Of course, performing all of your file storage logic within a Closure can cause your resource to become bloated. For
that reason, Restify allows you to pass an "Storable" class to the `store` method:

```php
File::make('avatar')->store(AvatarStore::class),
```

The storable class should be a simple PHP class that implements the `Binaryk\LaravelRestify\Repositories\Storable` contract:

```php
<?php

namespace Binaryk\LaravelRestify\Tests\Fixtures\User;

use Binaryk\LaravelRestify\Repositories\Storable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AvatarStore implements Storable
{
    public function handle(Request $request, Model $model, $attribute): array
    {
        return [
            'avatar' => $request->file('avatar')->storeAs('/', 'avatar.jpg', 'customDisk')
        ];
    }
}
```

### Command

<alert>
You can use the <code>php artisan restify:store AvatarStore</code> command to generate a store file.
</alert>

## Utility Methods

### Repository Management

Fields can be assigned to repositories programmatically:

```php
$field = Field::new('title');
$field->setRepository($repository);
$field->setParentRepository($parentRepository);
```

These methods are primarily used internally by Restify but can be useful when building custom field logic.

### Legacy Methods

#### Deprecated append() Method

<alert type="warning">

The `append()` method has been deprecated in favor of `value()`. Use `value()` instead:

```php
// Deprecated
field('user_id')->append(Auth::id());

// Recommended  
field('user_id')->value(Auth::id());
```

</alert>
