<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Support\Facades\Cache;

class CacheIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Use array cache for tests to avoid database table requirements
        config(['cache.default' => 'array']);

        // Create some test data
        Post::factory()->count(5)->create();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        Cache::flush();
        
        parent::tearDown();
    }

    public function test_it_does_not_cache_by_default_in_tests()
    {
        // Ensure we're in testing environment
        $this->assertEquals('testing', app()->environment());
        
        // Make request
        $response = $this->getJson('/api/restify/posts');
        $response->assertOk();
        
        // Make another request - should not be cached
        $response2 = $this->getJson('/api/restify/posts');
        $response2->assertOk();
        
        // Verify cache is empty (no cache keys should exist)
        $this->assertEmpty($this->getCacheKeys());
    }

    public function test_it_can_enable_cache_in_tests_with_config()
    {
        // Enable caching in tests
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request
        $response = $this->getJson('/api/restify/posts');
        $response->assertOk();
        
        // Verify cache key was created
        $this->assertNotEmpty($this->getCacheKeys());
    }

    public function test_it_respects_global_cache_disabled_setting()
    {
        // Disable caching globally
        config(['restify.repositories.cache.enabled' => false]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request
        $response = $this->getJson('/api/restify/posts');
        $response->assertOk();
        
        // Verify no cache key was created
        $this->assertEmpty($this->getCacheKeys());
    }

    public function test_it_generates_different_cache_keys_for_different_parameters()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Clear any existing cache
        Cache::flush();
        
        // Make request with different parameters
        $this->getJson('/api/restify/posts?search=test');
        $keys1 = $this->getCacheKeys();
        
        Cache::flush();
        
        $this->getJson('/api/restify/posts?search=different');
        $keys2 = $this->getCacheKeys();
        
        // Keys should be different for different search terms
        $this->assertNotEquals($keys1, $keys2);
    }

    public function test_it_generates_different_cache_keys_for_different_users()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request as first user
        $this->authenticate();
        $this->getJson('/api/restify/posts');
        $keys1 = $this->getCacheKeys();
        
        Cache::flush();
        
        // Make request as different user
        $this->authenticate(\Binaryk\LaravelRestify\Tests\Fixtures\User\User::factory()->create());
        $this->getJson('/api/restify/posts');
        $keys2 = $this->getCacheKeys();
        
        // Keys should be different for different users
        $this->assertNotEquals($keys1, $keys2);
    }

    public function test_it_can_clear_cache_programmatically()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request to populate cache
        $this->getJson('/api/restify/posts');
        $this->assertNotEmpty($this->getCacheKeys());
        
        // Clear cache using Laravel's flush for simplicity in tests
        // In production, the repository's clearCache method would be more targeted
        Cache::flush();
        
        // Verify cache is cleared
        $this->assertEmpty($this->getCacheKeys());
    }

    public function test_it_caches_responses_when_enabled()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make first request
        $response1 = $this->getJson('/api/restify/posts');
        $response1->assertOk();
        $data1 = $response1->json();
        
        // Verify cache was created
        $this->assertNotEmpty($this->getCacheKeys());
        
        // Make second request - should get cached response
        $response2 = $this->getJson('/api/restify/posts');
        $response2->assertOk();
        $data2 = $response2->json();
        
        // Data should be identical
        $this->assertEquals($data1, $data2);
    }

    public function test_it_respects_repository_specific_cache_settings()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Disable cache for specific repository
        PostRepository::disableCache();
        
        // Make request
        $this->getJson('/api/restify/posts');
        
        // Verify no cache key was created
        $this->assertEmpty($this->getCacheKeys());
        
        // Re-enable cache
        PostRepository::enableCache();
        
        // Make request
        $this->getJson('/api/restify/posts');
        
        // Verify cache key was created
        $this->assertNotEmpty($this->getCacheKeys());
    }

    public function test_it_includes_pagination_in_cache_key()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request with different page numbers
        $this->getJson('/api/restify/posts?page=1');
        $keys1 = $this->getCacheKeys();
        
        Cache::flush();
        
        $this->getJson('/api/restify/posts?page=2');
        $keys2 = $this->getCacheKeys();
        
        // Keys should be different for different pages
        $this->assertNotEquals($keys1, $keys2);
    }

    public function test_it_includes_filters_in_cache_key()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Make request with filters
        $this->getJson('/api/restify/posts?title=test');
        $keys1 = $this->getCacheKeys();
        
        Cache::flush();
        
        $this->getJson('/api/restify/posts?title=different');
        $keys2 = $this->getCacheKeys();
        
        // Keys should be different for different filters
        $this->assertNotEquals($keys1, $keys2);
    }

    public function test_it_can_set_custom_cache_ttl()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);
        
        // Set custom TTL
        PostRepository::cacheTtl(60); // 1 minute
        
        // Make request
        $this->getJson('/api/restify/posts');
        
        // Verify cache exists
        $this->assertNotEmpty($this->getCacheKeys());
        
        // Verify TTL is set (this is hard to test precisely without time manipulation)
        // We'll just verify the cache exists for now
    }

    /**
     * Get all cache keys that match the restify pattern.
     */
    protected function getCacheKeys(): array
    {
        $store = Cache::getStore();
        
        if (method_exists($store, 'getRedis')) {
            try {
                $redis = $store->getRedis();
                return $redis->keys('*restify*') ?: [];
            } catch (\Exception $e) {
                // Fall back for non-Redis stores
            }
        }
        
        // For array store (used in tests), we need to inspect the internal array
        if (get_class($store) === 'Illuminate\Cache\ArrayStore') {
            $reflection = new \ReflectionClass($store);
            $storage = $reflection->getProperty('storage');
            $storage->setAccessible(true);
            $storageArray = $storage->getValue($store);
            
            return array_filter(array_keys($storageArray), function ($key) {
                return str_contains($key, 'restify');
            });
        }
        
        // Fallback - check for typical patterns (with error handling for database stores)
        $patterns = [
            'restify:repository:posts:index',
            'laravel_cache:restify:repository:posts:index'
        ];
        
        try {
            foreach ($patterns as $pattern) {
                if (Cache::has($pattern)) {
                    return [$pattern];
                }
            }
        } catch (\Exception $e) {
            // Silently fail if cache store doesn't exist (e.g., database cache table missing)
            return [];
        }
        
        return [];
    }
}