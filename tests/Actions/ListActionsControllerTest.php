<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ListActionsControllerTest extends IntegrationTestCase
{
    public function test_could_list_actions_for_repository(): void
    {
        $_SERVER['actions.posts.invalidate'] = false;

        $this->getJson(PostRepository::route('actions'))
            ->assertOk()
            ->assertJson(
                fn (AssertableJson $json) => $json
                ->count('data', 1)
                ->where('data.0.uriKey', 'publish-post-action')
                ->etc()
            );
    }

    public function test_could_list_actions_for_given_repository(): void
    {
        $this->mockPosts(1, 2);

        $_SERVER['actions.posts.invalidate'] = true;
        $_SERVER['actions.posts.publish.onlyOnShow'] = true;

        $this->getJson(PostRepository::route('1/actions'))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 2)
                    ->where('data.0.uriKey', 'publish-post-action')
                    ->where('data.1.uriKey', 'invalidate-post-action')
                    ->etc()
            );
    }

    public function test_can_list_actions_only_for_show(): void
    {
        $this->mockPosts(1, 2);

        $_SERVER['actions.posts.onlyOnShow'] = true;
        $_SERVER['actions.posts.publish.onlyOnShow'] = false;

        $this->getJson(PostRepository::route('1/actions'))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 1)
                    ->where('data.0.uriKey', 'invalidate-post-action')
                    ->etc()
            );

        $this->getJson(PostRepository::route('actions'))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 1)
                    ->where('data.0.uriKey', 'publish-post-action')
                    ->etc()
            );

        $_SERVER['actions.posts.onlyOnShow'] = false;
        $_SERVER['actions.posts.publish.onlyOnShow'] = false;

        $this->getJson(PostRepository::route('1/actions'))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 0)
                    ->etc()
            );

        $this->getJson(PostRepository::route('actions'))
            ->assertSuccessful()
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->count('data', 2)
                    ->where('data.0.uriKey', 'publish-post-action')
                    ->where('data.1.uriKey', 'invalidate-post-action')
                    ->etc()
            );
    }
}
