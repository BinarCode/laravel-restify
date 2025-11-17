<?php

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsFilteredQueryGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowInvokableGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Post;
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

    public function test_getter_can_access_filtered_query(): void
    {
        // Create 5 posts: 3 active, 2 inactive
        Post::factory()->count(3)->create(['is_active' => true]);
        Post::factory()->count(2)->create(['is_active' => false]);

        // Call getter without filter - should return all 5 posts
        $this
            ->getJson(PostRepository::getter(PostsFilteredQueryGetter::class))
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'filtered query works')
                    ->where('count', 5)
                    ->etc()
            );

        // Call getter with active filter - should return only 3 active posts
        $this
            ->getJson(PostRepository::getter(PostsFilteredQueryGetter::class).'?is_active=1')
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'filtered query works')
                    ->where('count', 3)
                    ->etc()
            );

        // Call getter with inactive filter - should return only 2 inactive posts
        $this
            ->getJson(PostRepository::getter(PostsFilteredQueryGetter::class).'?is_active=0')
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('message', 'filtered query works')
                    ->where('count', 2)
                    ->etc()
            );
    }
}
