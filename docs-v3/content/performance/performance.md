---
title: Performance
menuTitle: Performance
description: Performance
category: Advanced
position: 14
---

## Policy Caching

When loading a large number of models, Restify will check each policy method as `show` or `allowRestify` (including for all relations) before serializing them.

In order to improve performance, Restify caches the policies. You simply have to enable the caching by setting the `restify.cache.policies.enabled` property to `true` in the `restify.php` configuration file:

```php
'cache' => [
    'policies' => [
        'enabled' => true,
        'ttl' => 5 * 60, // seconds
    ],
],
```

The caching is tight to the current authenticated user so if another user is logged in, the cache will be hydrated for the new user once again.

Restify allows individual caching at the policy level with specific configurations. To enable this, a contract `Cacheable` must be implemented at the policy level, which enforces the use of the `cache()` method.

``` php
class PostPolicy implements Cacheable
{
    public function cache(): ?CarbonInterface
    {
        return now()->addMinutes();
    }
```
The `cache` method is expected to return a `CarbonInterface` or `null`. If `null` is returned, the current policy will `NOT` cached.

## Disable index meta

Index meta are policy information related to what actions are allowed on a resource for a specific user. However, if you don't need this information, you can disable the index meta by setting the `restify.repositories.serialize_index_meta` property to `false` in the `restify.php` configuration file:

```php
'repositories' => [
    'serialize_index_meta' => false,
    
    'serialize_show_meta' => true,
],
```

This will give your application a boost, especially when loading a large amount of resources or relations.

## Repository Index Caching

Laravel Restify provides powerful caching for repository index requests to dramatically improve performance for expensive queries with filters, searches, sorts, and pagination. This feature can reduce response times by orders of magnitude for complex API endpoints.

### Quick Setup

Enable repository caching in your `.env` file:

```bash
# Enable repository index caching
RESTIFY_REPOSITORY_CACHE_ENABLED=true

# Cache TTL in seconds (default: 300 = 5 minutes)
RESTIFY_REPOSITORY_CACHE_TTL=300

# Optional: Specify cache store
RESTIFY_REPOSITORY_CACHE_STORE=redis
```

That's it! Your repository index endpoints will now be cached automatically.

### Configuration

All caching options are available in `config/restify.php`:

```php
'repositories' => [
    'cache' => [
        // Enable or disable caching globally
        'enabled' => env('RESTIFY_REPOSITORY_CACHE_ENABLED', false),
        
        // Default TTL in seconds
        'ttl' => env('RESTIFY_REPOSITORY_CACHE_TTL', 300),
        
        // Cache store to use (null = default)
        'store' => env('RESTIFY_REPOSITORY_CACHE_STORE'),
        
        // Skip caching for authenticated users
        'skip_authenticated' => false,
        
        // Enable in test environment (disabled by default)
        'enable_in_tests' => false,
        
        // Cache tags for efficient invalidation
        'tags' => ['restify', 'repositories'],
    ],
],
```

### Repository-Specific Configuration

Customize caching per repository:

```php
class PostRepository extends Repository
{
    // Disable caching for this repository
    public static bool $cacheEnabled = false;
    
    // Custom TTL (10 minutes)
    public static int $cacheTtl = 600;
    
    // Use specific cache store
    public static ?string $cacheStore = 'redis';
    
    // Custom cache tags
    public static array $cacheTags = ['posts', 'content'];
}
```

### Smart Cache Keys

The system generates unique cache keys based on:

- Repository type (users, posts, etc.)
- Request parameters (search, filters, sorting, pagination)
- User context (for authorization-sensitive data)
- Model timestamps (for automatic invalidation)

Example cache key:
```
restify:repository:posts:index:7ed77bab35bfc8f3fd4da03ffdde2370:user_1:v_1756392802
```

### Cache Store Compatibility

**Full Support (with cache tags):**
- ✅ Redis Store
- ✅ Memcached Store
- ✅ Array Store (testing)

**Basic Support (TTL-based):**
- ✅ Database Store
- ✅ File Store

The system automatically detects cache store capabilities and gracefully falls back when advanced features aren't supported.

### Automatic Cache Invalidation

Cache is automatically cleared when:

```php
// Model events trigger cache clearing
$post = Post::create([...]);  // Clears post cache
$post->update([...]);         // Clears post cache
$post->delete();              // Clears post cache
```

### Manual Cache Management

```php
// Clear cache for specific repository
PostRepository::clearCache();

// Configure caching at runtime
PostRepository::enableCache();
PostRepository::disableCache();
PostRepository::cacheTtl(600); // 10 minutes
PostRepository::cacheTags(['posts', 'content']);
```

### Performance Impact

Caching provides dramatic performance improvements:

- **Complex filters**: 50-90% faster response times
- **Large datasets**: Reduces database load significantly  
- **Pagination**: Instant subsequent page loads
- **Search queries**: Eliminates expensive LIKE operations
- **Authorization**: Caches user-specific policy checks

### Test Environment Safety

**Caching is disabled by default in tests** to prevent test isolation issues:

```php
// Tests automatically have caching disabled
class MyTest extends TestCase {
    public function test_something() {
        // Caching is off - no cache pollution between tests
    }
}

// Enable caching for specific tests
class CacheTest extends TestCase {
    public function test_with_cache() {
        $this->enableRepositoryCache();
        // Now caching is enabled for this test
    }
}
```

### Best Practices

1. **Production Focused**: Enable caching in production where it matters most
2. **Monitor TTL**: Set appropriate cache TTL based on data update frequency
3. **Use Redis**: Redis provides the best caching experience with full tag support
4. **Tag Strategy**: Use meaningful cache tags for efficient bulk invalidation
5. **Authorization-Aware**: Caching respects user permissions automatically

### Example Usage

```php
// Before caching: 500ms response time
GET /api/restify/posts?search=laravel&sort=created_at&page=2

// After caching: 20ms response time (25x faster!)
GET /api/restify/posts?search=laravel&sort=created_at&page=2

// Different parameters = different cache
GET /api/restify/posts?search=php&sort=title&page=1 // New cache entry

// Cache respects user context
// User A and User B get different cached results based on permissions
```

This caching system provides a significant performance boost with zero code changes required - simply enable it in configuration and enjoy faster API responses!
