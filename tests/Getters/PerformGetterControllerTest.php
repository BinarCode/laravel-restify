<?php

namespace Binaryk\LaravelRestify\Tests\Getters;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

class PerformGetterControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureLoggedIn();
    }

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
}
