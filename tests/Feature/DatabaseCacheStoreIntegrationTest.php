<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;

class DatabaseCacheStoreIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up database cache for this test
        config(['cache.default' => 'database']);

        // Create cache table
        if (! \Illuminate\Support\Facades\Schema::hasTable('cache')) {
            \Illuminate\Support\Facades\Schema::create('cache', function ($table) {
                $table->string('key')->unique();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        // Create some test data
        Post::factory()->count(3)->create();
    }

    protected function tearDown(): void
    {
        // Drop cache table
        \Illuminate\Support\Facades\Schema::dropIfExists('cache');

        parent::tearDown();
    }

    public function test_caching_works_with_database_store_without_tagging_errors()
    {
        // Enable caching with database store
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);

        // Set cache tags to test that they don't cause errors
        PostRepository::$cacheTags = ['posts', 'test'];

        // Make request - should work without tagging errors
        $response = $this->getJson('/api/restify/posts');
        $response->assertOk();

        $data1 = $response->json();

        // Make second request - should get cached result
        $response2 = $this->getJson('/api/restify/posts');
        $response2->assertOk();

        $data2 = $response2->json();

        // Data should be identical (from cache)
        $this->assertEquals($data1, $data2);

        // Verify cache actually exists in database
        $this->assertDatabaseHas('cache', [
            // Cache key should exist but we can't predict the exact key due to timestamps/hashing
        ]);

        $cacheCount = \Illuminate\Support\Facades\DB::table('cache')->count();
        $this->assertGreaterThan(0, $cacheCount, 'Cache entries should exist in database');
    }

    public function test_database_store_does_not_support_tagging()
    {
        $store = \Illuminate\Support\Facades\Cache::store('database');

        // Should return false for database store
        $this->assertFalse(PostRepository::cacheStoreSupportsTagging($store));
    }

    public function test_cache_clearing_works_with_database_store()
    {
        config(['restify.repositories.cache.enabled' => true]);
        config(['restify.repositories.cache.enable_in_tests' => true]);

        // Make request to populate cache
        $this->getJson('/api/restify/posts');

        // Verify cache exists
        $cacheCount = \Illuminate\Support\Facades\DB::table('cache')->count();
        $this->assertGreaterThan(0, $cacheCount);

        // Clear cache - should not throw errors even with tags
        PostRepository::$cacheTags = ['posts', 'test'];
        PostRepository::clearCache();

        // Cache should be cleared
        $cacheCountAfter = \Illuminate\Support\Facades\DB::table('cache')->count();
        $this->assertEquals(0, $cacheCountAfter, 'Cache should be cleared after clearCache()');
    }
}
