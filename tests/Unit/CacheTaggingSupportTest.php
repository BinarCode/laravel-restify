<?php

namespace Binaryk\LaravelRestify\Tests\Unit;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use Mockery;

class CacheTaggingSupportTest extends IntegrationTestCase
{
    public function test_array_store_supports_tagging()
    {
        $store = new ArrayStore;

        $this->assertTrue(PostRepository::cacheStoreSupportsTagging($store));
    }

    public function test_database_store_does_not_support_tagging()
    {
        // Mock a store that throws the expected exception
        $store = Mockery::mock();
        $store->shouldReceive('tags')->andThrow(new \BadMethodCallException('This cache store does not support tagging.'));
        $store->shouldReceive('getStore')->andReturnSelf();

        $this->assertFalse(PostRepository::cacheStoreSupportsTagging($store));
    }

    public function test_cache_store_without_tags_method()
    {
        // Mock a store that doesn't have the tags method
        $store = Mockery::mock();
        // Don't set up tags method, so method_exists will return false

        $this->assertFalse(PostRepository::cacheStoreSupportsTagging($store));
    }

    public function test_tagging_detection_prevents_errors()
    {
        // Test that we can detect non-supporting stores without exceptions
        $mockStore = Mockery::mock();
        $mockStore->shouldReceive('tags')->andThrow(new \BadMethodCallException('This cache store does not support tagging.'));
        $mockStore->shouldReceive('getStore')->andReturnSelf();

        // This should not throw an exception
        $supportsTagging = PostRepository::cacheStoreSupportsTagging($mockStore);

        $this->assertFalse($supportsTagging);
    }

    protected function tearDown(): void
    {
        // Reset repository cache tags
        PostRepository::$cacheTags = [];

        parent::tearDown();
    }
}
