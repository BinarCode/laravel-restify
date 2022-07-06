<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Restify;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsIndexGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\PostsShowGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\Getters\UnauthenticatedActionGetter;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\Request;
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

    public function test_unauthenticated_user_can_access_middleware_when_except_auth(): void
    {
        $this->markTestSkipped('will implement sometime');

        Restify::$authUsing = static function (Request $request) {
            return ! is_null($request->user());
        };

        $this
            ->withoutExceptionHandling()
            ->getJson(PostRepository::getter(UnauthenticatedActionGetter::class))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->etc()
            );

        Restify::$authUsing = static function () {
            return true;
        };
    }
}
