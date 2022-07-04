<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Testing\Fluent\AssertableJson;

// TODO: Please refactor all tests using assertJson (as the first test does).
class ListActionsControllerTest extends IntegrationTest
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

        $this->getJson('posts/1/actions')
            ->assertSuccessful()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'name',
                        'uriKey',
                    ],
                ],
            ]);
    }

    public function test_can_list_actions_only_for_show()
    {
        $this->mockPosts(1, 2);

        $_SERVER['actions.posts.onlyOnShow'] = true;
        $_SERVER['actions.posts.publish.onlyOnShow'] = false;

        $response = $this->getJson('posts/1/actions')
            ->assertJsonCount(1, 'data');

        $this->assertEquals('invalidate-post-action', $response->json('data.0.uriKey'));

        $response = $this->getJson('posts/actions')
            ->assertJsonCount(1, 'data');

        $this->assertEquals('publish-post-action', $response->json('data.0.uriKey'));

        $_SERVER['actions.posts.onlyOnShow'] = false;
        $_SERVER['actions.posts.publish.onlyOnShow'] = false;

        $this->getJson('posts/1/actions')
            ->assertJsonCount(0, 'data');

        $response = $this->getJson('posts/actions')
            ->assertJsonCount(2, 'data');

        $this->assertEquals('publish-post-action', $response->json('data.0.uriKey'));
        $this->assertEquals('invalidate-post-action', $response->json('data.1.uriKey'));
    }
}
