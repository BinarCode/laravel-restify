<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;

class RepositoryCacheStoreConfigTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        config(['cache.stores.secondary' => ['driver' => 'array']]);

        $this->enableRepositoryCache();

        Post::factory()->count(3)->create();
    }

    protected function tearDown(): void
    {
        PostRepository::$cacheStore = null;

        Cache::store('array')->flush();
        Cache::store('secondary')->flush();

        parent::tearDown();
    }

    #[Test]
    public function the_configured_store_is_used_when_the_repository_has_no_explicit_store(): void
    {
        config(['restify.repositories.cache.store' => 'secondary']);

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertNotEmpty($this->restifyKeysIn('secondary'));
        $this->assertEmpty($this->restifyKeysIn('array'));

        $fresh = Post::factory()->create(['title' => 'fresh-after-clear-secondary']);

        PostRepository::clearCache();

        $this->getJson('/api/restify/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => $fresh->title]);
    }

    #[Test]
    public function the_repository_s_own_store_overrides_the_configured_store(): void
    {
        config(['restify.repositories.cache.store' => 'secondary']);
        PostRepository::$cacheStore = 'array';

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertNotEmpty($this->restifyKeysIn('array'));
        $this->assertEmpty($this->restifyKeysIn('secondary'));

        $fresh = Post::factory()->create(['title' => 'fresh-after-clear-array']);

        PostRepository::clearCache();

        $this->getJson('/api/restify/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => $fresh->title]);
    }

    #[Test]
    public function an_empty_configured_store_falls_back_to_the_default_store(): void
    {
        config(['restify.repositories.cache.store' => null]);

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertNotEmpty($this->restifyKeysIn('array'));
        $this->assertEmpty($this->restifyKeysIn('secondary'));
    }

    /**
     * @return list<string>
     */
    private function restifyKeysIn(string $storeName): array
    {
        $store = Cache::store($storeName)->getStore();

        if (! $store instanceof ArrayStore) {
            return [];
        }

        $reflection = new \ReflectionClass($store);
        $storage = $reflection->getProperty('storage');
        $storage->setAccessible(true);

        /** @var array<string, mixed> $storageArray */
        $storageArray = $storage->getValue($store);

        return array_values(array_filter(
            array_keys($storageArray),
            fn (string $key): bool => str_contains($key, 'restify:repository:posts:index')
        ));
    }
}
