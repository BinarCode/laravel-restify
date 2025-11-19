<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Cache\PolicyCache;
use Binaryk\LaravelRestify\Tests\Database\Factories\PostFactory;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class AuthorizableModelsTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('restify.cache.policies.enabled', true);
        config()->set('cache.default', 'file');

        $_SERVER['restify.post.allowRestify'] = true;

        Cache::flush();
    }

    public function test_can_cache_allow_restify_policy_so_its_called_once_per_user(): void
    {
        $this->partialMock(PostPolicy::class)
            ->shouldReceive('allowRestify')
            ->twice()
            ->andReturn(false);

        Gate::policy(Post::class, PostPolicy::class);

        $_SERVER['restify.post.allowRestify'] = false;

        $this->getJson(PostRepository::route())
            ->assertForbidden();

        $this->getJson(PostRepository::route())
            ->assertForbidden();

        $this->authenticate(User::factory()->state(['id' => 2])->create());

        $this->getJson(PostRepository::route())
            ->assertForbidden();
    }

    public function test_can_cache_show_policy_so_its_called_once_per_user_per_model(): void
    {
        $this->partialMock(PostPolicy::class)
            ->shouldReceive('show')
            ->once()
            ->andReturn(true);

        Gate::policy(Post::class, PostPolicy::class);

        $post = PostFactory::one();

        $this->getJson(PostRepository::route($post))
            ->assertOk();
        $this->getJson(PostRepository::route($post))
            ->assertOk();

        // for 2 models and the same user it'll be called twice (once for each model)
        $this->partialMock(PostPolicy::class)
            ->shouldReceive('show')
            ->twice()
            ->andReturn(true);

        PostFactory::many()->each(function (Post $post) {
            $this->getJson(PostRepository::route($post))
                ->assertOk();
            $this->getJson(PostRepository::route($post))
                ->assertOk();
        });

        // for 2 models and different users it'll be called 4 times (once for each model and user)
        $this->partialMock(PostPolicy::class)
            ->shouldReceive('show')
            ->times(4)
            ->andReturn(true);

        $this->authenticate(User::factory()->create());
        PostFactory::many()->each(function (Post $post) {
            $this->getJson(PostRepository::route($post))
                ->assertOk();
            $this->getJson(PostRepository::route($post))
                ->assertOk();
        });

        $this->authenticate(User::factory()->create());
        PostFactory::many()->each(function (Post $post) {
            $this->getJson(PostRepository::route($post))
                ->assertOk();
            $this->getJson(PostRepository::route($post))
                ->assertOk();
        });
    }

    public function test_can_disable_policies_cache(): void
    {
        config()->set('restify.cache.policies.enabled', false);

        $this->partialMock(PostPolicy::class)
            ->shouldReceive('allowRestify')
            ->times(3)
            ->andReturn(false);

        $this->getJson(PostRepository::route())
            ->assertForbidden();

        $this->getJson(PostRepository::route())
            ->assertForbidden();

        $this->getJson(PostRepository::route())
            ->assertForbidden();
    }

    public function test_can_enable_individual_policy_cache(): void
    {
        $this->freezeTime();

        $this->partialMock(PostPolicy::class)
            ->shouldReceive('show')
            ->andReturn(true);

        $this->partialMock(PostPolicy::class)
            ->shouldReceive('cache')
            ->once()
            ->andReturn(now()->addMinutes(3));

        Gate::policy(Post::class, PostPolicy::class);

        /** @var Carbon $timeCached */
        $timeCached = Gate::getPolicyFor(Post::class)
            ->cache();

        $this->assertInstanceOf(CarbonInterface::class, $timeCached);

        $post = PostFactory::one();

        $this->getJson(PostRepository::route())
            ->assertOk();

        $this->assertTrue(Cache::has(PolicyCache::keyForPolicyMethods('posts', 'show', $post->getKey())));

        $this->partialMock(PostPolicy::class)
            ->shouldReceive('show')
            ->andReturn(false);

        $this->getJson(PostRepository::route())
            ->assertOk();

        $this->assertTrue(Cache::get(PolicyCache::keyForPolicyMethods('posts', 'show', $post->getKey())));

        $this->travelTo(now()->addMinutes(5));

        $this->assertFalse(Cache::has(PolicyCache::keyForPolicyMethods('posts', 'show', $post->getKey())));
    }
}
