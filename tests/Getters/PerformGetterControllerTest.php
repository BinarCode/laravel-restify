<?php

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PerformGetterControllerTest extends IntegrationTestCase
{
    public function test_could_perform_getter(): void
    {
        $this
            ->getJson(PostRepository::getter(PostsIndexGetter::class))
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'it works')
                    ->etc()
            );
    }

    public function test_could_perform_invokable_getter(): void
    {
        $this
            ->getJson(PostRepository::getter(PostsIndexInvokableGetter::class))
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'invokable works')
                    ->etc()
            );
    }

    public function test_could_perform_repository_getter(): void
    {
        $this->mockPosts(1, 2);

        $this
            ->getJson(PostRepository::getter(PostsShowGetter::class, 1))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'show works')
                    ->etc()
            );
    }

    public function test_could_perform_repository_invokable_getter(): void
    {
        $this->mockPosts(1, 2);

        $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::getter(PostsShowInvokableGetter::class, 1))
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'show works')
                    ->etc()
            );
    }
}
