<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Cache\Cacheable;
use Binaryk\LaravelRestify\Cache\PolicyCache;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\CarbonInterface;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

class PolicyCacheTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.cache.policies.enabled', true);
        config()->set('cache.default', 'array');

        Cache::flush();
    }

    #[Test]
    public function a_cache_hit_reads_the_cache_exactly_once(): void
    {
        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->once()->andReturn(new class {});

        $key = 'restify.policy.test.hit-once';
        $calls = 0;
        $data = function () use (&$calls) {
            $calls++;

            return true;
        };

        PolicyCache::resolve($key, $data, $model);

        Event::fake([CacheHit::class, CacheMissed::class]);

        $result = PolicyCache::resolve($key, $data, $model);

        $this->assertTrue($result);
        $this->assertSame(1, $calls);
        Event::assertDispatchedTimes(CacheHit::class, 1);
        Event::assertNotDispatched(CacheMissed::class);
    }

    #[Test]
    public function a_cached_false_result_is_returned_as_false_without_rerunning_the_policy(): void
    {
        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->once()->andReturn(new class {});

        $key = 'restify.policy.test.cached-false';
        $calls = 0;
        $data = function () use (&$calls) {
            $calls++;

            return false;
        };

        $first = PolicyCache::resolve($key, $data, $model);
        $second = PolicyCache::resolve($key, $data, $model);

        $this->assertFalse($first);
        $this->assertFalse($second);
        $this->assertSame(1, $calls);
    }

    #[Test]
    public function a_null_ttl_returns_the_evaluated_result_and_is_not_cached(): void
    {
        $model = new Post;

        $policy = new class implements Cacheable
        {
            public function cache(): ?CarbonInterface
            {
                return null;
            }
        };

        Gate::shouldReceive('getPolicyFor')->andReturn($policy);

        $key = 'restify.policy.test.null-ttl';
        $calls = 0;
        $data = function () use (&$calls) {
            $calls++;

            return false;
        };

        $result = PolicyCache::resolve($key, $data, $model);

        $this->assertFalse($result);
        $this->assertSame(1, $calls);

        PolicyCache::resolve($key, $data, $model);

        $this->assertSame(2, $calls, 'a null ttl must not cache the result, so the closure runs again');
        $this->assertFalse(Cache::has($key));
    }

    #[Test]
    public function disabled_cache_calls_the_policy_closure_every_time(): void
    {
        config()->set('restify.cache.policies.enabled', false);

        $model = new Post;

        $calls = 0;
        $data = function () use (&$calls) {
            $calls++;

            return true;
        };

        $key = 'restify.policy.test.disabled';

        PolicyCache::resolve($key, $data, $model);
        PolicyCache::resolve($key, $data, $model);
        PolicyCache::resolve($key, $data, $model);

        $this->assertSame(3, $calls);
    }

    #[Test]
    #[TestWith([300], 'integer ttl')]
    #[TestWith(['300'], 'numeric-string ttl, as env() returns it')]
    public function a_configured_global_ttl_caches_the_result(int|string $configuredTtl): void
    {
        config()->set('restify.cache.policies.ttl', $configuredTtl);

        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->once()->andReturn(new class {});

        $key = 'restify.policy.test.configured-ttl';
        $calls = 0;
        $data = function () use (&$calls) {
            $calls++;

            return true;
        };

        $first = PolicyCache::resolve($key, $data, $model);
        $second = PolicyCache::resolve($key, $data, $model);

        $this->assertTrue($first);
        $this->assertTrue($second);
        $this->assertSame(1, $calls);
        $this->assertTrue(Cache::has($key));
    }

    #[Test]
    public function different_user_classes_with_the_same_auth_id_get_different_cache_keys(): void
    {
        $userA = new class implements Authenticatable
        {
            use AuthenticatableTrait;

            public function getAuthIdentifier(): int
            {
                return 1;
            }
        };

        $userB = new class implements Authenticatable
        {
            use AuthenticatableTrait;

            public function getAuthIdentifier(): int
            {
                return 1;
            }
        };

        app(Request::class)->setUserResolver(fn () => $userA);
        $keyForA = PolicyCache::keyForAllowRestify('posts');

        app(Request::class)->setUserResolver(fn () => $userB);
        $keyForB = PolicyCache::keyForAllowRestify('posts');

        $this->assertNotSame($keyForA, $keyForB);

        Cache::put($keyForA, 'cached-for-a', 60);

        $this->assertTrue(Cache::has($keyForA));
        $this->assertFalse(Cache::has($keyForB));
    }
}
