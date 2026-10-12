<?php

namespace Binaryk\LaravelRestify\Repositories\Concerns;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ReflectionProperty;

trait InteractsWithCache
{
    private const CACHE_VERSION_TTL_DAYS = 30;

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
     *
     * @var array<int, string>
     */
    public static array $cacheTags = [];

    /**
     * @var array<class-string, int>
     */
    private static array $cacheTtls = [];

    /**
     * @var array<class-string, bool>
     */
    private static array $cacheEnabledStates = [];

    /**
     * @var array<class-string, string|null>
     */
    private static array $cacheStoreNames = [];

    /**
     * @var array<class-string, array<int, string>>
     */
    private static array $cacheTagsByRepository = [];

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

        $pagination = $request->pagination();

        // Add request parameters that affect the query
        $queryParams = [
            'search' => $request->input('search'),
            'sort' => $request->input('sort'),
            'page' => $pagination->resolvePage() ?? 1,
            'perPage' => $pagination->resolvePerPage(static::$defaultPerPage),
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
        /** @var Authenticatable|null $user */
        $user = $request->user();

        if ($user !== null) {
            $keyParts[] = 'guard_'.Auth::getDefaultDriver();
            $keyParts[] = 'model_'.($user instanceof Model ? $user->getMorphClass() : $user::class);
            $keyParts[] = 'user_'.$user->getAuthIdentifier();
        } else {
            $keyParts[] = 'guest';
        }

        // Add model version/timestamp for automatic invalidation
        if (method_exists($this->model(), 'getUpdatedAtColumn')) {
            try {
                $updatedAtColumn = $this->model()->getUpdatedAtColumn();

                $latest = $this->model()->newQuery()->max($updatedAtColumn);

                if ($latest) {
                    $keyParts[] = 'v_'.$this->castToTimestamp($latest, $updatedAtColumn);
                }
            } catch (\Exception $e) {
                // Fallback to current timestamp if query fails
                $keyParts[] = 'v_'.now()->timestamp;
            }
        }

        $keyParts[] = 'ver_'.static::cacheVersion();

        return implode(':', $keyParts);
    }

    private function castToTimestamp(mixed $value, string $column): int
    {
        $cast = $this->model()->newFromBuilder([$column => $value])->{$column};

        return $cast instanceof DateTimeInterface
            ? $cast->getTimestamp()
            : CarbonImmutable::parse($value)->getTimestamp();
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
        if (! static::cacheIsEnabled()) {
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
        $ttl = static::resolveCacheTtl();
        $cacheTags = static::resolveCacheTags();

        if (static::cacheStoreSupportsTagging($store)) {
            return $store->tags($cacheTags)->remember($cacheKey, $ttl, $callback);
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

        try {
            Cache::store(static::resolveCacheStoreName())->put(
                static::cacheVersionKey(),
                Str::random(),
                now()->addDays(self::CACHE_VERSION_TTL_DAYS),
            );
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected static function cacheVersionKey(): string
    {
        return 'restify:repository:'.static::uriKey().':version';
    }

    protected static function cacheVersion(): string
    {
        $store = Cache::store(static::resolveCacheStoreName());
        $key = static::cacheVersionKey();

        $version = $store->get($key);

        if (is_string($version)) {
            return $version;
        }

        $candidate = Str::random();
        $store->add($key, $candidate, now()->addDays(self::CACHE_VERSION_TTL_DAYS));

        $stored = $store->get($key);

        return is_string($stored) ? $stored : $candidate;
    }

    protected static function cacheIsEnabled(): bool
    {
        $configured = self::configuredCacheValue(self::$cacheEnabledStates, 'cacheEnabled');

        return $configured === null ? static::$cacheEnabled : (bool) $configured['value'];
    }

    protected static function resolveCacheTtl(): int
    {
        $configured = self::configuredCacheValue(self::$cacheTtls, 'cacheTtl');

        if ($configured !== null && is_int($configured['value'])) {
            return $configured['value'];
        }

        $declaredDefault = (new ReflectionProperty(self::class, 'cacheTtl'))->getDefaultValue();

        if (static::$cacheTtl !== $declaredDefault) {
            return static::$cacheTtl;
        }

        $configuredTtl = config('restify.repositories.cache.ttl');

        return is_numeric($configuredTtl) ? (int) $configuredTtl : static::$cacheTtl;
    }

    /**
     * The nearest value a setter or a redeclared property provides, walking from
     * this repository up to the class using the trait. A setter beats a redeclared
     * property on the same class, and a child beats its parents.
     *
     * @param  array<class-string, mixed>  $setterValues
     * @return array{value: mixed}|null
     */
    private static function configuredCacheValue(array $setterValues, string $property): ?array
    {
        $class = static::class;

        while ($class !== false) {
            if (array_key_exists($class, $setterValues)) {
                return ['value' => $setterValues[$class]];
            }

            $reflection = new ReflectionProperty($class, $property);

            if ($class !== self::class && $reflection->getDeclaringClass()->getName() === $class) {
                return ['value' => $reflection->getValue()];
            }

            $class = $class === self::class ? false : get_parent_class($class);
        }

        return null;
    }

    /**
     * The cache tags for this repository, including its default tags.
     *
     * @return array<int, string>
     */
    protected static function resolveCacheTags(): array
    {
        return array_values(array_unique(array_merge(
            ['restify', 'repositories', static::uriKey()],
            static::$cacheTags,
            ...self::cacheTagsFromSetters(),
        )));
    }

    /**
     * @return list<array<int, string>>
     */
    private static function cacheTagsFromSetters(): array
    {
        $tags = [];
        $class = static::class;

        while ($class !== false) {
            array_unshift($tags, self::$cacheTagsByRepository[$class] ?? []);

            $class = $class === self::class ? false : get_parent_class($class);
        }

        return $tags;
    }

    /**
     * Get the cache store instance.
     */
    protected function getCacheStore()
    {
        return Cache::store(static::resolveCacheStoreName());
    }

    protected static function resolveCacheStoreName(): ?string
    {
        $configured = self::configuredCacheValue(self::$cacheStoreNames, 'cacheStore');
        $explicitStore = $configured === null ? static::$cacheStore : $configured['value'];

        if (is_string($explicitStore)) {
            return $explicitStore;
        }

        $configuredStore = config('restify.repositories.cache.store');

        return is_string($configuredStore) && $configuredStore !== '' ? $configuredStore : null;
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
     *
     * @param  array<int, string>  $tags
     */
    public static function cacheTags(array $tags): void
    {
        self::$cacheTagsByRepository[static::class] = array_values(array_unique(array_merge(
            self::$cacheTagsByRepository[static::class] ?? [],
            $tags,
        )));
    }

    /**
     * Set cache TTL for this repository.
     */
    public static function cacheTtl(int $seconds): void
    {
        self::$cacheTtls[static::class] = $seconds;
    }

    /**
     * Set the cache store for this repository.
     */
    public static function cacheStore(?string $store): void
    {
        self::$cacheStoreNames[static::class] = $store;
    }

    /**
     * Disable caching for this repository.
     */
    public static function disableCache(): void
    {
        self::$cacheEnabledStates[static::class] = false;
    }

    /**
     * Enable caching for this repository.
     */
    public static function enableCache(): void
    {
        self::$cacheEnabledStates[static::class] = true;
    }

    /**
     * Boot the InteractsWithCache trait.
     */
    protected static function bootInteractsWithCache(): void
    {
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
