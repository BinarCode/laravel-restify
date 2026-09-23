<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Tests\Concerns\InteractsWithQueryLog;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\Carbon;
use Illuminate\Cache\ArrayStore;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionClass;

class RepositoryCacheStoreConfigTest extends IntegrationTestCase
{
    use InteractsWithQueryLog;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());

        config(['cache.default' => 'array']);
        config(['cache.stores.secondary' => ['driver' => 'array']]);

        $this->enableRepositoryCache();

        Post::factory()->count(3)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

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

        $key = $this->indexCacheKey();

        $this->assertTrue(
            Cache::store('secondary')->tags(PostRepository::$cacheTags)->has($key),
            'the index response should have been cached under the configured store',
        );

        $fresh = Post::factory()->create(['title' => 'fresh-after-clear-secondary']);

        PostRepository::clearCache();

        $this->assertFalse(
            Cache::store('secondary')->tags(PostRepository::$cacheTags)->has($key),
            'clearCache() should invalidate the entry it wrote to the configured store',
        );

        $this->getJson('/api/restify/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => $fresh->title]);
    }

    #[Test]
    public function an_explicit_repository_store_overrides_the_configured_store(): void
    {
        config(['restify.repositories.cache.store' => 'secondary']);
        PostRepository::$cacheStore = 'array';

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertNotEmpty($this->restifyKeysIn('array'));
        $this->assertEmpty($this->restifyKeysIn('secondary'));

        $key = $this->indexCacheKey();

        $this->assertTrue(
            Cache::store('array')->tags(PostRepository::$cacheTags)->has($key),
            'the index response should have been cached under the repository\'s own store',
        );

        $fresh = Post::factory()->create(['title' => 'fresh-after-clear-array']);

        PostRepository::clearCache();

        $this->assertFalse(
            Cache::store('array')->tags(PostRepository::$cacheTags)->has($key),
            'clearCache() should invalidate the entry it wrote to the repository\'s own store',
        );

        $this->getJson('/api/restify/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => $fresh->title]);
    }

    #[Test]
    public function a_repeated_request_against_the_configured_store_is_served_from_the_cache(): void
    {
        config(['restify.repositories.cache.store' => 'secondary']);

        $this->getJson('/api/restify/posts')->assertOk();

        $this->recordQueries();

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertSelectCount(1, Post::class);
    }

    #[Test]
    #[TestWith([null], 'null')]
    #[TestWith([''], 'empty string')]
    public function a_falsy_configured_store_falls_back_to_the_default_store(?string $configuredStore): void
    {
        config(['restify.repositories.cache.store' => $configuredStore]);

        $this->getJson('/api/restify/posts')->assertOk();

        $this->assertNotEmpty($this->restifyKeysIn('array'));
        $this->assertEmpty($this->restifyKeysIn('secondary'));
    }

    private function indexCacheKey(): string
    {
        return PostRepository::resolveWith(new Post)
            ->generateIndexCacheKey(app(RestifyRequest::class));
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

        $reflection = new ReflectionClass($store);
        $storage = $reflection->getProperty('storage');

        /** @var array<string, mixed> $storageArray */
        $storageArray = $storage->getValue($store);

        return array_values(array_filter(
            array_keys($storageArray),
            fn (string $key): bool => str_contains($key, 'restify:repository:posts:index')
        ));
    }
}
