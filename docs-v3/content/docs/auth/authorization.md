---
title: Authorization
menuTitle: Authorization
category: Auth
position: 1
---

Laravel Restify provides a unified authorization system that protects both your REST API endpoints and MCP server tools using Laravel's built-in authorization features. This ensures that both human users and AI agents follow the same security rules when accessing your resources.

## Unified Authorization

Restify extends Laravel's policy system to work seamlessly across different access patterns:

- **REST API requests** from web applications and mobile apps
- **MCP tool calls** from AI agents like Claude or custom AI systems
- **Profile endpoints** for authenticated user data
- **Bulk operations** for efficient data management

All authorization rules are defined once in your Laravel policies and automatically applied to both REST endpoints and MCP tools, maintaining consistent security across your entire API. 

## Request lifecycle

Before diving into authorization details, it's important to understand the actual request lifecycle. This way, you'll know what to expect and how to debug your app at any point.

### Booting

When you make a request (e.g., via Postman), it hits the Laravel application. Laravel will load every Service Provider defined in `config/app.php` and [auto-discovered](https://laravel.com/docs/packages#package-discovery) providers as well.

Restify injects the `RestifyApplicationServiceProvider` in your `config/app.php` and also has an auto-discovered provider called `\Binaryk\LaravelRestify\LaravelRestifyServiceProvider`.

- The `LaravelRestifyServiceProvider` is booted first. This pushes the `RestifyInjector` middleware to the end of the middleware stack. 

- Then, the `RestifyApplicationServiceProvider` is booted. This defines the gate, loads repositories, and creates the auth routes macro. You have full control over this provider.

- The `RestifyInjector` is handled. It registers all the routes.

- On each request, if the requested route is a Restify route, Laravel will handle other middleware defined in `restify.php` -> `middleware`. This is where you should have the `auth:sanctum` middleware to protect your API against unauthenticated users.

## Prerequisites

Before we dive into the details of authorization, we need to make sure that you have a basic understanding of how Laravel's authorization works. If you are not familiar with it, we highly recommend reading the [documentation](https://laravel.com/docs/authorization) before you move forward.

You may also visit the [Authentication/login](/auth/authentication#authorization) section to learn how to login and use the Bearer token.


## View Restify

The global `viewRestify` gate is the first authorization checkpoint that controls access to all Restify repositories. This gate applies to both REST API endpoints and MCP server access.

<alert>This gate is only active in non-local environments.</alert>

### REST API Configuration

The gate is defined in your `RestifyApplicationServiceProvider`:

```php [app/Providers/RestifyServiceProvider.php]
protected function gate()
{
    Gate::define('viewRestify', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
            'editor@example.com',
        ]);
    });
}
```

### MCP Server Configuration

To apply the same `viewRestify` gate to your MCP server, configure it in your `config/ai.php` routes:

```php
use Binaryk\LaravelRestify\MCP\RestifyServer;
use Binaryk\LaravelRestify\Http\Middleware\AuthorizeRestify;
use Laravel\Mcp\Facades\Mcp;

// MCP server with the same authorization rules as REST API
Mcp::web('restify', RestifyServer::class)
    ->middleware(['auth:sanctum', AuthorizeRestify::class])
    ->name('mcp.restify');
```

The `AuthorizeRestify` middleware automatically checks the `viewRestify` gate, ensuring that both REST endpoints and MCP tools use the same access control rules.

### Common Gate Patterns

**Recommended: Allow all authenticated users (most common approach):**

In most applications, you'll want to allow all authenticated users to access Restify and then control specific permissions through individual model policies:

```php
Gate::define('viewRestify', function ($user) {
    return $user !== null; // Allow any authenticated user
});
```

This approach is recommended because it provides maximum flexibility - you can then use individual model policies to control exactly what each user can do with each resource type.

**Role-based access:**
```php
Gate::define('viewRestify', function ($user) {
    return $user?->hasAnyRole(['admin', 'editor', 'api-user']);
});
```

**Permission-based access:**
```php
Gate::define('viewRestify', function ($user) {
    return $user?->hasPermissionTo('access-api');
});
```

**Allow unauthenticated access:**
```php
Gate::define('viewRestify', function ($user = null) {
    return true; // Use with caution - relies entirely on model policies
});
```

**Environment-specific access:**
```php
Gate::define('viewRestify', function ($user = null) {
    // Full access in development
    if (app()->environment(['local', 'testing'])) {
        return true;
    }
    
    // Production: authenticated users with verified emails
    return $user?->hasVerifiedEmail();
});
```

### Important Notes

- **Unified Authorization**: The same gate controls both REST API and MCP server access when using the `AuthorizeRestify` middleware
- **Recommended Pattern**: Most applications should return `true` for all authenticated users in `viewRestify` and handle specific permissions through model policies
- **Flexibility**: Using `return $user !== null` allows you to define granular permissions at the model level rather than at the global API level
- **Policy-Driven Security**: Individual model policies provide fine-grained control over who can perform specific actions on specific resources

**Best Practice**: Return `true` for authenticated users in `viewRestify` and implement detailed authorization logic in your model policies. This approach gives you maximum flexibility while maintaining security.

## Policies

If you are not aware of what a policy is, we highly recommend reading the [documentation](https://laravel.com/docs/authorization#creating-policies) before you move forward.

You can use the Laravel command for generating a policy. It is greatly recommended to generate a policy using the Restify command because it will scaffold Restify's CRUD authorization methods for you:

```shell script
php artisan restify:policy UserPolicy
```

It will automatically detect the `User` model (the word before `Policy`). However, you can also specify the model explicitly: 

```shell script
php artisan restify:policy PostPolicy --model=Post
```

<alert>
The model is assumed to be in the `app/Models` directory.
</alert>

<alert type="warning">
By default, Restify will deny any requests if there isn't a defined policy method associated with the request's endpoint. If you don't have a policy at all, all requests from that repository will be unauthorized.
</alert>

If you already have a policy, here is the Restify default scaffolded one so you can apply these methods on your own:

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function allowRestify(User $user = null): bool
    {
        //
    }

    public function show(User $user, Post $model): bool
    {
        //
    }

    public function store(User $user): bool
    {
        //
    }

    public function storeBulk(User $user): bool
    {
        //
    }

    public function update(User $user, Post $model): bool
    {
        //
    }

    public function updateBulk(User $user, Post $model): bool
    {
        //
    }

    public function delete(User $user, Post $model): bool
    {
        //
    }

    public function deleteBulk(User $user, Post $model): bool
    {
        //
    }

    public function restore(User $user, Post $model): bool
    {
        //
    }

    public function forceDelete(User $user, Post $model): bool
    {
        //
    }
}
```

<alert type="info">
For the examples below, we will use PostRepository as our example.
</alert>

### Allow restify

This is the gateway method that determines if a user can access any operations on this repository. This applies to both REST API requests and MCP tool calls.

```php
// PostPolicy
/**
 * Determine whether the user can access this repository.
 * This controls access to both REST endpoints and MCP tools.
 */
public function allowRestify(User $user = null): bool
{
    // Example 1: Allow all authenticated users
    return $user !== null;
    
    // Example 2: Role-based access
    // return $user?->hasRole('editor') || $user?->hasRole('admin');
    
    // Example 3: Permission-based access
    // return $user?->can('manage-posts');
}
```

**Common patterns:**

```php
// Allow specific roles
public function allowRestify(User $user = null): bool
{
    return $user?->hasAnyRole(['admin', 'editor', 'author']);
}

// Allow based on permissions
public function allowRestify(User $user = null): bool
{
    return $user?->hasPermissionTo('access-posts-api');
}

// Different access for different environments
public function allowRestify(User $user = null): bool
{
    if (app()->environment('local')) {
        return true; // Full access in development
    }
    
    return $user?->isVerified() && $user?->hasRole('content-manager');
}
```

### Allow show

Controls read access to individual records. This applies to both listing endpoints and individual resource retrieval, as well as MCP tools that query data.

**Routes affected:**
- `GET: /api/restify/posts` (filters out unauthorized records from pagination)
- `GET: /api/restify/posts/{id}` (returns 403 if unauthorized)
- MCP tools: `posts-show-tool`, `posts-index-tool`

```php
/**
 * Determine whether the user can view the post.
 */
public function show(User $user, Post $model): bool
{
    // Example 1: Public posts or owned posts
    return $model->is_public || $user->id === $model->author_id;
    
    // Example 2: Published posts or draft posts for owners
    // return $model->published_at || $user->id === $model->author_id;
    
    // Example 3: Role-based with ownership
    // return $user->hasRole('admin') || $user->id === $model->author_id;
}
```

**Advanced patterns:**

```php
// Status-based authorization
public function show(User $user, Post $model): bool
{
    // Admins see everything
    if ($user->hasRole('admin')) {
        return true;
    }
    
    // Authors see their own posts
    if ($user->id === $model->author_id) {
        return true;
    }
    
    // Regular users see published posts only
    return $model->status === 'published';
}

// Time-based authorization
public function show(User $user, Post $model): bool
{
    // Draft posts only visible to author
    if ($model->status === 'draft') {
        return $user->id === $model->author_id;
    }
    
    // Scheduled posts only visible after publish date
    if ($model->status === 'scheduled') {
        return $model->published_at <= now() || $user->id === $model->author_id;
    }
    
    return true; // Published posts visible to all
}
```

### Allow store

Controls the ability to create new records via both REST API and MCP tools.

**Routes affected:**
- `POST: /api/restify/posts`
- MCP tools: `posts-store-tool`

```php
/**
 * Determine whether the user can create posts.
 */
public function store(User $user): bool
{
    // Example 1: Only verified users can create posts
    return $user->hasVerifiedEmail();
    
    // Example 2: Role-based creation
    // return $user->hasAnyRole(['author', 'editor', 'admin']);
    
    // Example 3: Quota-based creation
    // return $user->posts()->where('created_at', '>=', today())->count() < 5;
}
```

**Advanced patterns:**

```php
// Subscription-based authorization
public function store(User $user): bool
{
    // Free users: limited posts
    if ($user->subscription_type === 'free') {
        return $user->posts()->count() < 10;
    }
    
    // Premium users: unlimited
    return $user->subscription_type === 'premium';
}

// Time-based restrictions
public function store(User $user): bool
{
    // New users must wait 24 hours
    if ($user->created_at->gt(now()->subDay())) {
        return false;
    }
    
    // Rate limiting: max 3 posts per hour
    $recentPosts = $user->posts()
        ->where('created_at', '>=', now()->subHour())
        ->count();
        
    return $recentPosts < 3;
}
```
### Allow storeBulk

Determine if the user can store multiple entities at once.

The `storeBulk` method corresponds to the following route:

```http request
POST: /api/restify/posts/bulk
```

Definition:

```php
/**
 * Determine whether the user can create multiple models at once.
 *
 * @param User $user
 * @return mixed
 */
public function storeBulk(User $user)
{
    //
}
```

### Allow update

Determine if the user can update a specific model.

The `update` method corresponds to the following routes:

::code-group

```http [Full Update]
PUT: api/restify/posts/{id}
```

```http [Partial Update]
PATCH: api/restify/posts/{id}
```

```http [File uploads]
POST: api/restify/posts/{id}
```

::

Definition:

```php
/**
 * Determine whether the user can update the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function update(User $user, Post $model)
{
    //
}
```

### Allow updateBulk
Determine if the user can update multiple entities at once. When you bulk update, this method will be invoked for each entity you're trying to update. If at least one will return false - none will be updated. The reason behind that is that the bulk update is a DB transaction.

The `updateBulk` method, corresponds to the following route:

```http request
POST: /api/restify/posts/bulk/update
```

Definition:
```php
/**
 * Determine whether the user can update bulk the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function updateBulk(User $user = null, Post $model)
{
    return true;
}
```

### Allow delete

The delete endpoint policy.

The `delete` method, corresponds to the following route:

```http request
DELETE: api/restify/posts/{id}
```

Definition:

```php
/**
 * Determine whether the user can delete the model.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function delete(User $user, Post $model)
{
    //
}
```


### Allow deleteBulk

Determine if the user can delete multiple entities at once. When performing bulk deletion, this method will be invoked for each entity you're trying to delete.

The deleteBulk method corresponds to the following route:

```http request
DELETE: /api/restify/posts/bulk/delete
```

Definition:

```php
/**
 * Determine whether the user can delete multiple models at once.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function deleteBulk(User $user, Post $model)
{
    //
}
```


### Allow Attach

<alert type="warning">

Here is where we're talking about pivot tables. Many to many relationships.

</alert>

When attaching a model to another, we should check if the user is able to do that. For example, attaching posts to a user:

```http request
POST: /api/restify/users/{id}/attach/posts
```
```json
{ "posts": [1, 2, 3] }
```

Restify will guess the policy's name by the related entity. For this reason, it will be `attachPost`:

```php [UserPolicy.php]
/**
 * Determine if the post could be attached to the user.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function attachPost(User $user, Post $model)
{
    return $user->is($model->creator()->first());
}
```

The `attachPost` method will be called for each individual post.

### Allow Detach

<alert type="warning">

Here we're talking about pivot tables. Many to many relationships.

</alert>

When detaching a model from another, we should check if the user is able to do that. For example, detaching posts from a user:

```http request
POST: /api/restify/users/{id}/detach/posts
```
```json
{ "posts": [1, 2, 3] }
```

 Restify will guess the policy's name by the related entity. For this reason, it will be `detachPost`:

```php
/**
 * Determine if the post could be attached to the user.
 *
 * @param User $user
 * @param Post $model
 * @return mixed
 */
public function detachPost(User $user, Post $model)
{
    return $user->is($model->creator()->first());
}
```

The `detachPost` method will be called for each individual post.

## MCP Authorization

When AI agents access your API through the MCP server, they use the same authentication and authorization system as regular API requests. This ensures consistent security across all access patterns.

### Authentication for AI Agents

AI agents use Laravel Sanctum Bearer tokens for authentication, but they don't authenticate directly. Instead, a human user generates a Sanctum token and provides it to the AI agent.

**Authentication Flow:**

1. **User generates a Sanctum token** for the AI agent to use
2. **Token is provided to the AI agent** (manually or through your application)
3. **AI agent uses the Bearer token** in all MCP tool calls
4. **Same authorization rules apply** based on the token's associated user

**Token Generation Options:**

You have several approaches for generating tokens for AI agents:

**Option 1: Use your own Bearer token**
If you want the AI agent to have the same permissions as yourself, you can simply give it your existing Bearer token:

```php
// Use your current authentication token
$yourToken = auth()->user()->currentAccessToken()->plainTextToken;
// Provide this token to your AI agent
```

**Quick Development Tip**: You can easily get your Bearer token by copying it from:
- The initial login request response in your browser's Network tab
- Any XHR request headers in your browser's Developer Tools
- Look for the `Authorization: Bearer your-token-here` header in the request headers

**Option 2: Generate a dedicated token for AI agents**
Create a specific token with appropriate scopes:

```php
// Generate a token with specific abilities/scopes
$token = auth()->user()->createToken('AI Agent Token', ['read', 'write'])->plainTextToken;
// Provide this token to your AI agent
```

**Option 3: Create dedicated AI user accounts**
For more control, create separate user accounts for AI agents:

```php
// Create AI agent user (one-time setup)
$aiUser = User::create([
    'name' => 'Claude AI Agent',
    'email' => 'ai-agent@your-app.com',
    'password' => Hash::make(Str::random(32)), // Random password
]);

// Assign appropriate roles/permissions
$aiUser->assignRole('ai-agent');

// Generate token for the AI user
$token = $aiUser->createToken('MCP Server Token')->plainTextToken;
// Provide this token to your AI agent
```

The MCP server will authenticate requests using the provided Bearer token and apply authorization policies based on the token's associated user account.

### MCP Tool Authorization

Each MCP tool respects the same policy methods as REST endpoints:

```php
// PostPolicy - these methods protect both REST and MCP access
public function show(User $user, Post $model): bool
{
    // This controls both:
    // - GET /api/restify/posts/{id}
    // - posts-show-tool MCP call
    return $user->can('view', $model);
}

public function store(User $user): bool
{
    // This controls both:
    // - POST /api/restify/posts
    // - posts-store-tool MCP call
    return $user->hasRole('content-creator');
}
```

### AI Agent User Setup

Create dedicated users for AI agents with appropriate roles:

```php
// Create AI agent user
$aiUser = User::create([
    'name' => 'Claude AI Agent',
    'email' => 'claude@ai.example.com',
    'password' => Hash::make('secure-password'),
    'email_verified_at' => now(),
]);

// Assign specific role for AI operations
$aiUser->assignRole('ai-agent');

// Or assign specific permissions
$aiUser->givePermissionTo(['read-posts', 'create-posts', 'update-own-posts']);
```

### Role-Based AI Authorization

Set up roles specifically for AI agents:

```php
// PostPolicy
public function store(User $user): bool
{
    return $user->hasAnyRole(['author', 'editor', 'ai-agent']);
}

public function show(User $user, Post $model): bool
{
    // AI agents can read all published content
    if ($user->hasRole('ai-agent')) {
        return $model->status === 'published';
    }
    
    // Regular user authorization
    return $user->id === $model->author_id || $model->is_public;
}
```

### Debugging MCP Authorization

Monitor AI agent authorization with logging:

```php
public function show(User $user, Post $model): bool
{
    $canView = $user->can('view', $model);
    
    // Log AI agent access attempts
    if ($user->hasRole('ai-agent')) {
        Log::info('AI agent access attempt', [
            'user' => $user->email,
            'post' => $model->id,
            'authorized' => $canView,
        ]);
    }
    
    return $canView;
}
```

## Register Policy

After creating your policies, you must register them in your `AuthServiceProvider`. This is crucial for both REST API and MCP authorization to work properly.

### Basic Registration

In `app/Providers/AuthServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Policies\PostPolicy;
use App\Policies\UserPolicy; 
use App\Policies\CommentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        Post::class => PostPolicy::class,
        User::class => UserPolicy::class,
        Comment::class => CommentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        
        // Additional gates or policies can be defined here
    }
}
```

### Auto-Discovery

Laravel can auto-discover policies if you follow naming conventions:

```php
// These will be auto-discovered:
// App\Models\Post -> App\Policies\PostPolicy
// App\Models\User -> App\Policies\UserPolicy
// App\Models\BlogPost -> App\Policies\BlogPostPolicy

// If using auto-discovery, you can leave $policies empty:
protected $policies = [];
```

### Testing Policy Registration

Verify your policies are registered correctly:

```php
// In a controller or Artisan command
public function testPolicies()
{
    $user = auth()->user();
    $post = Post::first();
    
    // These should work if policies are properly registered
    dd([
        'can_view' => $user->can('show', $post),
        'can_create' => $user->can('store', Post::class),
        'can_update' => $user->can('update', $post),
        'can_delete' => $user->can('delete', $post),
    ]);
}
```

### Common Registration Issues

**Problem**: Policy not being called
```php
// Wrong - model not registered
protected $policies = [
    // Missing: Post::class => PostPolicy::class,
];

// Fix - register the model-policy mapping
protected $policies = [
    Post::class => PostPolicy::class,
];
```

**Problem**: Namespace issues
```php
// Wrong - incorrect namespace
use Policies\PostPolicy; // Missing App\

// Fix - correct namespace
use App\Policies\PostPolicy;
```

### Advanced Registration Patterns

```php
public function boot(): void
{
    $this->registerPolicies();
    
    // Register multiple models to one policy
    Gate::define('manage-content', function (User $user) {
        return $user->hasRole('content-manager');
    });
    
    // Dynamic policy registration for modular apps
    foreach (config('modules.enabled', []) as $module) {
        $this->registerModulePolicies($module);
    }
}

private function registerModulePolicies(string $module): void
{
    $policiesPath = app_path("Modules/{$module}/Policies");
    
    if (is_dir($policiesPath)) {
        // Auto-register policies for this module
        // Implementation depends on your modular structure
    }
}
```

## Field-Level Authorization

Beyond model-level authorization, you can control access to specific fields within your repositories. This is particularly useful when different users should see or modify different attributes of the same resource.

### Repository Field Authorization

In your repository, you can conditionally include fields based on the current user's permissions:

```php
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;

class PostRepository extends Repository
{
    public function fields(RestifyRequest $request): array
    {
        $user = $request->user();
        
        $fields = [
            field('title')->required(),
            field('content')->required(),
            field('published_at')->rules('date'),
        ];
        
        // Only admins can see/edit sensitive fields
        if ($user->hasRole('admin')) {
            $fields[] = field('internal_notes');
            $fields[] = field('admin_flags');
        }
        
        // Only post owners can modify status
        if ($user->can('updateStatus', $this->model())) {
            $fields[] = field('status')->rules('in:draft,published,archived');
        }
        
        return $fields;
    }
}
```

### Conditional Field Rules

Apply different validation rules based on user permissions:

```php
public function fields(RestifyRequest $request): array
{
    $user = $request->user();
    
    return [
        field('title')
            ->rules($user->hasRole('admin') ? 'required|min:5' : 'required|min:10'),
            
        field('content')
            ->rules('required')
            ->when($user->hasRole('editor'), function ($field) {
                return $field->rules('min:500'); // Editors need longer content
            }),
            
        field('featured')
            ->rules('boolean')
            ->when($user->hasRole('admin'), function ($field) {
                return $field; // Only admins can set featured
            }),
    ];
}
```

### Related Fields Authorization

Control access to relationship fields:

```php
public static function related(): array
{
    $user = request()->user();
    $relations = [];
    
    // Everyone can see basic relations
    $relations['author'] = BelongsTo::make('user', UserRepository::class);
    $relations['comments'] = HasMany::make('comments', CommentRepository::class);
    
    // Only admins can see sensitive relations
    if ($user?->hasRole('admin')) {
        $relations['audit_logs'] = HasMany::make('auditLogs', AuditLogRepository::class);
        $relations['flags'] = MorphMany::make('flags', FlagRepository::class);
    }
    
    return $relations;
}
```

### Policy-Based Field Access

Define field access in your policies:

```php
// PostPolicy
public function viewSensitiveFields(User $user, Post $post): bool
{
    return $user->hasRole('admin') || $user->id === $post->author_id;
}

public function editMetaFields(User $user, Post $post): bool
{
    return $user->hasAnyRole(['admin', 'editor']);
}

// Then in your repository:
public function fields(RestifyRequest $request): array
{
    $user = $request->user();
    $post = $this->model();
    
    $fields = [
        field('title')->required(),
        field('content')->required(),
    ];
    
    if ($user->can('viewSensitiveFields', $post)) {
        $fields[] = field('draft_notes');
        $fields[] = field('seo_keywords');
    }
    
    if ($user->can('editMetaFields', $post)) {
        $fields[] = field('featured')->rules('boolean');
        $fields[] = field('priority')->rules('integer|min:1|max:10');
    }
    
    return $fields;
}
```

### Dynamic Field Filtering

Filter field values based on authorization:

```php
public function fields(RestifyRequest $request): array
{
    return [
        field('title')->required(),
        
        field('content')->required(),
        
        // Mask email for non-admins
        field('author_email')
            ->resolveUsing(function ($value) use ($request) {
                if ($request->user()?->hasRole('admin')) {
                    return $value;
                }
                
                return Str::mask($value, '*', 3);
            }),
            
        // Show full statistics only to authorized users
        field('view_count')
            ->hideFromIndex(fn($request) => !$request->user()?->can('viewAnalytics'))
            ->hideFromShow(fn($request) => !$request->user()?->can('viewAnalytics')),
    ];
}
```

This field-level authorization works seamlessly with both REST API requests and MCP tool calls, ensuring consistent access control across all interfaces.

For more details, see Laravel's [authorization documentation](https://laravel.com/docs/authorization#registering-policies).
