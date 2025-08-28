<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait InteractsWithCache
{
    /**
     * Whether caching is enabled for this repository.
     */
    public static bool $cacheEnabled = true;

    /**
     * Cache TTL in seconds for index requests.
     */
    public static int $cacheTtl = 300; // 5 minutes

    /**
     * Cache store to use for this repository.
     */
    public static ?string $cacheStore = null;

    /**
     * Cache tags for this repository.
     */
    public static array $cacheTags = [];

    /**
     * Generate a unique cache key for the index request.
     */
    public function generateIndexCacheKey(RestifyRequest $request): string
    {
        $keyParts = [
            'restify',
            'repository',
            static::uriKey(),
            'index',
        ];

        // Add request parameters that affect the query
        $queryParams = [
            'search' => $request->input('search'),
            'sort' => $request->input('sort'),
            'page' => $request->pagination()->page ?? 1,
            'perPage' => $request->pagination()->perPage ?? 15, // Default per page
            'related' => $request->input('related'),
            'group_by' => $request->input('group_by'),
        ];

        // Add filters
        foreach ($request->filters() as $key => $value) {
            if ($value !== null && $value !== '') {
                $queryParams["filter_{$key}"] = $value;
            }
        }

        // Add matches - get from request input since matches() is a static repository method
        $matches = $request->input('match', []);
        if (is_array($matches)) {
            foreach ($matches as $key => $value) {
                if ($value !== null && $value !== '') {
                    $queryParams["match_{$key}"] = $value;
                }
            }
        }

        // Sort and serialize parameters for consistent keys
        ksort($queryParams);
        $serializedParams = md5(serialize(array_filter($queryParams)));
        $keyParts[] = $serializedParams;

        // Add user context for authorization-sensitive data
        if ($user = $request->user()) {
            $keyParts[] = 'user_' . $user->getAuthIdentifier();
        } else {
            $keyParts[] = 'guest';
        }

        // Add model version/timestamp for automatic invalidation
        if (method_exists($this->model(), 'getUpdatedAtColumn')) {
            try {
                $latest = $this->model()::latest($this->model()->getUpdatedAtColumn())->first();
                if ($latest) {
                    $keyParts[] = 'v_' . $latest->{$this->model()->getUpdatedAtColumn()}->timestamp;
                }
            } catch (\Exception $e) {
                // Fallback to current timestamp if query fails
                $keyParts[] = 'v_' . now()->timestamp;
            }
        }

        return implode(':', $keyParts);
    }

    /**
     * Check if caching should be used for this request.
     */
    protected function shouldUseCache(RestifyRequest $request): bool
    {
        // Disable caching in test environment by default
        if (app()->environment('testing') && ! config('restify.repositories.cache.enable_in_tests', false)) {
            return false;
        }

        // Check if caching is globally disabled
        if (! config('restify.repositories.cache.enabled', false)) {
            return false;
        }

        // Check if caching is disabled for this specific repository
        if (! static::$cacheEnabled) {
            return false;
        }

        // Skip caching for authenticated requests if configured to do so
        if (config('restify.repositories.cache.skip_authenticated', false) && $request->user()) {
            return false;
        }

        return true;
    }

    /**
     * Get cached index data or execute the callback to generate it.
     */
    protected function cacheIndex(RestifyRequest $request, callable $callback): array
    {
        if (! $this->shouldUseCache($request)) {
            return $callback();
        }

        $cacheKey = $this->generateIndexCacheKey($request);
        $store = $this->getCacheStore();
        $ttl = static::$cacheTtl;

        // Use cache tags if available and supported
        if (! empty(static::$cacheTags) && static::cacheStoreSupportsTagging($store)) {
            return $store->tags(static::$cacheTags)->remember($cacheKey, $ttl, $callback);
        }

        return $store->remember($cacheKey, $ttl, $callback);
    }

    /**
     * Clear cache for this repository.
     */
    public static function clearCache(): void
    {
        // Skip cache clearing if caching is disabled globally
        if (! config('restify.repositories.cache.enabled', false)) {
            return;
        }

        // Skip cache clearing if disabled in test environment
        if (app()->environment('testing') && ! config('restify.repositories.cache.enable_in_tests', false)) {
            return;
        }

        // Skip cache clearing if disabled for this repository
        if (! static::$cacheEnabled) {
            return;
        }

        $store = Cache::store(static::$cacheStore);

        // If cache tags are used and supported, flush by tags
        if (! empty(static::$cacheTags) && static::cacheStoreSupportsTagging($store)) {
            $store->tags(static::$cacheTags)->flush();
            return;
        }

        // For ArrayStore (used in tests), manually clear matching keys
        if (get_class($store) === 'Illuminate\Cache\ArrayStore') {
            $reflection = new \ReflectionClass($store);
            $storage = $reflection->getProperty('storage');
            $storage->setAccessible(true);
            $storageArray = $storage->getValue($store);

            // Find and remove keys that contain 'restify' and our repository
            $keysToRemove = array_filter(array_keys($storageArray), function ($key) {
                return str_contains($key, 'restify') && (
                    str_contains($key, static::uriKey()) ||
                    str_contains($key, 'tag:restify')
                );
            });

            foreach ($keysToRemove as $key) {
                unset($storageArray[$key]);
            }

            $storage->setValue($store, $storageArray);
            return;
        }

        // For Redis and other drivers that support pattern deletion
        if (method_exists($store->getStore(), 'getRedis')) {
            try {
                $pattern = 'restify:repository:' . static::uriKey() . ':*';
                $store->getStore()->getRedis()->eval(
                    "return redis.call('del', unpack(redis.call('keys', ARGV[1])))",
                    0,
                    $pattern
                );
                return;
            } catch (\Exception $e) {
                // Continue to fallback
            }
        }

        // Fallback: flush entire cache (not ideal but better than stale data)
        try {
            $store->flush();
        } catch (\Exception $e) {
            // Silently fail if cache flushing fails (e.g., cache table doesn't exist)
            // This prevents cache operations from breaking the application
        }
    }

    /**
     * Get the cache store instance.
     */
    protected function getCacheStore()
    {
        return Cache::store(static::$cacheStore);
    }

    /**
     * Check if the cache store supports tagging.
     */
    public static function cacheStoreSupportsTagging($store): bool
    {
        // Check if the store has the tags method
        if (! method_exists($store, 'tags')) {
            return false;
        }

        // Get the underlying store driver
        $storeClass = get_class($store);
        $underlyingStore = method_exists($store, 'getStore') ? $store->getStore() : $store;
        $underlyingStoreClass = get_class($underlyingStore);

        // Known stores that support tagging
        $supportedStores = [
            'Illuminate\Cache\RedisStore',
            'Illuminate\Cache\MemcachedStore',
            'Illuminate\Cache\ArrayStore', // For testing
        ];

        // Known stores that don't support tagging
        $unsupportedStores = [
            'Illuminate\Cache\DatabaseStore',
            'Illuminate\Cache\FileStore',
            'Illuminate\Cache\NullStore',
            'Illuminate\Cache\DynamoDbStore',
        ];

        // Check if it's in the unsupported list
        if (in_array($underlyingStoreClass, $unsupportedStores)) {
            return false;
        }

        // Check if it's in the supported list
        if (in_array($underlyingStoreClass, $supportedStores)) {
            return true;
        }

        // For unknown stores, try to detect by attempting to use tags
        try {
            // Create a test tagged cache entry (without storing anything)
            $store->tags(['test'])->getStore();
            return true;
        } catch (\Exception $e) {
            // If it throws an exception about tagging not being supported, return false
            if (str_contains($e->getMessage(), 'does not support tagging') ||
                str_contains($e->getMessage(), 'not support tagging')) {
                return false;
            }

            // For other exceptions, we can't determine support, so return false to be safe
            return false;
        }
    }

    /**
     * Add cache tags for this repository.
     */
    public static function cacheTags(array $tags): void
    {
        static::$cacheTags = array_merge(static::$cacheTags, $tags);
    }

    /**
     * Set cache TTL for this repository.
     */
    public static function cacheTtl(int $seconds): void
    {
        static::$cacheTtl = $seconds;
    }

    /**
     * Disable caching for this repository.
     */
    public static function disableCache(): void
    {
        static::$cacheEnabled = false;
    }

    /**
     * Enable caching for this repository.
     */
    public static function enableCache(): void
    {
        static::$cacheEnabled = true;
    }

    /**
     * Boot the InteractsWithCache trait.
     */
    protected static function bootInteractsWithCache(): void
    {
        // Set default cache tags
        static::$cacheTags = array_merge(['restify', 'repositories', static::uriKey()], static::$cacheTags);

        // Listen to model events to clear cache automatically
        static::bindModelEvents();
    }

    /**
     * Bind model events to automatically clear cache.
     */
    protected static function bindModelEvents(): void
    {
        try {
            $model = static::newModel();

            if (! $model) {
                return;
            }

            $events = ['created', 'updated', 'deleted', 'restored'];

            foreach ($events as $event) {
                $model::$event(function () {
                    static::clearCache();
                });
            }
        } catch (\Exception $e) {
            // Silently fail if model events can't be bound
            // This prevents the cache system from breaking the repository
        }
    }
}
