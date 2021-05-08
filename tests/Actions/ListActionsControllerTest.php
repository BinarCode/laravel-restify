<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\IntegrationTest;

class ListActionsControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_list_actions_for_repository(): void
    {
        $_SERVER['actions.posts.invalidate'] = false;

        $this->withoutExceptionHandling()->getJson('posts/actions')
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'name',
                        'uriKey',
                    ],
                ],
            ]);
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
