<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostPolicy;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\User\User;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class AuthorizableModelsTest extends IntegrationTest
{
    use RefreshDatabase;

    public function test_can_cache_allowRestify_policy_so_its_called_once_per_user(): void
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
}
