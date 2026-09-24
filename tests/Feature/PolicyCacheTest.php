<?php

declare(strict_types=1);

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Cache\Cacheable;
use Binaryk\LaravelRestify\Cache\PolicyCache;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
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
use Stringable;

class PolicyCacheTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.cache.policies.enabled', true);
        config()->set('cache.default', 'array');

        Cache::flush();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['restify.post.allowRestify']);

        parent::tearDown();
    }

    #[Test]
    public function a_cache_hit_reads_the_cache_exactly_once(): void
    {
        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->once()->andReturn(new class {});

        $key = 'restify.policy.test.hit-once';
        $calls = 0;
        $data = function () use (&$calls): bool {
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
        $data = function () use (&$calls): bool {
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
        $data = function () use (&$calls): bool {
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
        $data = function () use (&$calls): bool {
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
        $data = function () use (&$calls): bool {
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

            public function getKey(): int
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

            public function getKey(): int
            {
                return 1;
            }
        };

        app(Request::class)->setUserResolver(fn (): Authenticatable => $userA);
        $keyForA = PolicyCache::keyForAllowRestify('posts');

        app(Request::class)->setUserResolver(fn (): Authenticatable => $userB);
        $keyForB = PolicyCache::keyForAllowRestify('posts');

        $this->assertNotSame($keyForA, $keyForB);

        Cache::put($keyForA, 'cached-for-a', 60);

        $this->assertTrue(Cache::has($keyForA));
        $this->assertFalse(Cache::has($keyForB));
    }

    #[Test]
    public function an_authenticated_user_pins_the_user_class_and_id_key_format(): void
    {
        $user = (new User)->forceFill(['id' => 777]);

        $this->authenticate($user);

        $this->getJson(PostRepository::route())->assertOk();

        $this->assertTrue(
            Cache::has('restify.policy.allowRestify.repository-posts.user-'.User::class.'#777')
        );
    }

    #[Test]
    #[TestWith([777, '777'], 'integer id')]
    #[TestWith(['user-uuid-1', 'user-uuid-1'], 'string id')]
    public function the_user_key_stringifies_a_scalar_auth_identifier(int|string $id, string $expectedIdSegment): void
    {
        $user = $this->userWithAuthIdentifier($id);

        app(Request::class)->setUserResolver(fn (): Authenticatable => $user);

        $key = PolicyCache::keyForAllowRestify('posts');

        $this->assertSame(
            'restify.policy.allowRestify.repository-posts.user-'.$user::class.'#'.$expectedIdSegment,
            $key
        );
    }

    #[Test]
    public function two_users_with_different_stringable_auth_identifiers_get_different_cache_keys(): void
    {
        $identifierA = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'uuid-aaaa';
            }
        };

        $identifierB = new class implements Stringable
        {
            public function __toString(): string
            {
                return 'uuid-bbbb';
            }
        };

        app(Request::class)->setUserResolver(fn (): Authenticatable => $this->userWithAuthIdentifier($identifierA));
        $keyForA = PolicyCache::keyForAllowRestify('posts');

        app(Request::class)->setUserResolver(fn (): Authenticatable => $this->userWithAuthIdentifier($identifierB));
        $keyForB = PolicyCache::keyForAllowRestify('posts');

        $this->assertNotSame($keyForA, $keyForB);
        $this->assertStringEndsWith('#uuid-aaaa', $keyForA);
        $this->assertStringEndsWith('#uuid-bbbb', $keyForB);
    }

    #[Test]
    public function a_non_stringable_auth_identifier_returns_null_instead_of_a_key(): void
    {
        app(Request::class)->setUserResolver(fn (): Authenticatable => $this->userWithAuthIdentifier(new class {}));

        $this->assertNull(PolicyCache::keyForAllowRestify('posts'));
    }

    #[Test]
    public function a_guest_request_produces_the_same_empty_user_segment_as_before(): void
    {
        app(Request::class)->setUserResolver(fn (): ?Authenticatable => null);

        $key = PolicyCache::keyForAllowRestify('posts');

        $this->assertSame('restify.policy.allowRestify.repository-posts.user-', $key);
    }

    #[Test]
    public function an_authenticated_user_with_a_null_auth_identifier_returns_null_instead_of_a_key(): void
    {
        app(Request::class)->setUserResolver(fn (): Authenticatable => $this->userWithAuthIdentifier(null));

        $this->assertNull(PolicyCache::keyForAllowRestify('posts'));
    }

    #[Test]
    public function a_disabled_cache_never_builds_a_key_so_a_present_user_with_a_null_auth_identifier_does_not_throw(): void
    {
        config()->set('restify.cache.policies.enabled', false);

        $this->authenticate(User::factory()->make());

        $this->getJson(PostRepository::route())->assertOk();
    }

    #[Test]
    public function a_present_user_with_a_null_auth_identifier_is_evaluated_but_never_cached(): void
    {
        $this->authenticate(User::factory()->make());

        $_SERVER['restify.post.allowRestify'] = true;
        $this->getJson(PostRepository::route())->assertOk();

        $_SERVER['restify.post.allowRestify'] = false;
        $this->getJson(PostRepository::route())->assertForbidden();
    }

    #[Test]
    public function a_null_model_key_is_never_cached(): void
    {
        $model = new Post;

        Cache::spy();

        $result = PolicyCache::resolve(
            fn (): ?string => PolicyCache::keyForPolicyMethods('posts', 'show', null),
            fn (): bool => true,
            $model,
        );

        $this->assertTrue($result);
        Cache::shouldNotHaveReceived('put');
        Cache::shouldNotHaveReceived('get');
    }

    #[Test]
    #[TestWith([null], 'null ttl')]
    #[TestWith(['abc'], 'non-numeric string ttl')]
    #[TestWith([true], 'boolean ttl')]
    #[TestWith([0], 'zero ttl')]
    #[TestWith([-5], 'negative ttl')]
    public function an_invalid_or_non_positive_global_ttl_never_caches_the_result(mixed $configuredTtl): void
    {
        config()->set('restify.cache.policies.ttl', $configuredTtl);

        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->andReturn(new class {});

        $key = 'restify.policy.test.invalid-ttl';
        $calls = 0;
        $data = function () use (&$calls): bool {
            $calls++;

            return true;
        };

        PolicyCache::resolve($key, $data, $model);
        PolicyCache::resolve($key, $data, $model);

        $this->assertSame(2, $calls, 'an invalid or non-positive ttl must not cache, so the closure runs every time');
        $this->assertFalse(Cache::has($key));
    }

    #[Test]
    #[TestWith([1.9], 'float ttl')]
    #[TestWith(['1e3'], 'scientific-notation numeric-string ttl')]
    public function a_fractional_or_scientific_notation_global_ttl_caches_the_result(float|string $configuredTtl): void
    {
        config()->set('restify.cache.policies.ttl', $configuredTtl);

        $model = new Post;

        Gate::shouldReceive('getPolicyFor')->once()->andReturn(new class {});

        $key = 'restify.policy.test.fractional-ttl';
        $calls = 0;
        $data = function () use (&$calls): bool {
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
    public function a_disabled_cache_never_evaluates_the_key_closure(): void
    {
        config()->set('restify.cache.policies.enabled', false);

        $model = new Post;

        $keyEvaluations = 0;
        $key = function () use (&$keyEvaluations): string {
            $keyEvaluations++;

            return 'restify.policy.test.key-should-not-run';
        };

        PolicyCache::resolve($key, fn (): bool => true, $model);

        $this->assertSame(0, $keyEvaluations);
    }

    #[Test]
    public function a_disabled_cache_never_touches_the_cache_store_over_a_real_request(): void
    {
        config()->set('restify.cache.policies.enabled', false);

        $this->authenticate(User::factory()->make());

        Cache::spy();

        $this->getJson(PostRepository::route())->assertOk();

        Cache::shouldNotHaveReceived('get');
        Cache::shouldNotHaveReceived('put');
        Cache::shouldNotHaveReceived('has');
    }

    #[Test]
    public function an_iterable_ability_with_caching_enabled_bypasses_the_cache_instead_of_erroring(): void
    {
        $repository = PostRepository::resolveWith(new Post);

        Cache::spy();

        $authorized = $repository->authorizedTo(app(Request::class), ['show']);

        $this->assertTrue($authorized);
        Cache::shouldNotHaveReceived('get');
        Cache::shouldNotHaveReceived('put');
    }

    private function userWithAuthIdentifier(mixed $id): Authenticatable
    {
        return new class($id) implements Authenticatable
        {
            use AuthenticatableTrait;

            public function __construct(private readonly mixed $id) {}

            public function getAuthIdentifier(): mixed
            {
                return $this->id;
            }
        };
    }
}
