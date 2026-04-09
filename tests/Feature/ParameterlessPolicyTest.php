<?php

namespace Binaryk\LaravelRestify\Tests\Feature;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class ParameterlessPolicyTest extends IntegrationTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['restify.parameterless.allowRestify'] = true;
    }

    public function test_unauthenticated_request_is_allowed_when_parameterless_allow_restify_returns_true(): void
    {
        Gate::policy(Post::class, ParameterlessAllowPolicy::class);

        $this->logout();

        $_SERVER['restify.parameterless.allowRestify'] = true;

        $this->getJson(PostRepository::route())
            ->assertOk();
    }

    public function test_unauthenticated_request_is_denied_when_parameterless_allow_restify_returns_false(): void
    {
        Gate::policy(Post::class, ParameterlessAllowPolicy::class);

        $this->logout();

        $_SERVER['restify.parameterless.allowRestify'] = false;

        $this->getJson(PostRepository::route())
            ->assertForbidden();
    }

    public function test_authenticated_request_is_allowed_when_parameterless_allow_restify_returns_true(): void
    {
        Gate::policy(Post::class, ParameterlessAllowPolicy::class);

        $_SERVER['restify.parameterless.allowRestify'] = true;

        $this->getJson(PostRepository::route())
            ->assertOk();
    }
}

/**
 * A policy where allowRestify() has zero parameters — no $user argument at all.
 *
 * Laravel's Gate denies guest access when a policy method is missing a nullable
 * $user parameter.  The checkPolicyMethod() helper in AuthorizableModels detects
 * this and calls the method directly, bypassing Gate's guest-check logic.
 */
class ParameterlessAllowPolicy
{
    public function allowRestify(): bool
    {
        return $_SERVER['restify.parameterless.allowRestify'] ?? true;
    }

    public function show(): bool
    {
        return true;
    }

    public function store(): bool
    {
        return true;
    }
}
