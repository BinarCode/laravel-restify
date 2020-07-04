<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class ListActionsControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_list_actions_for_repository()
    {
        $_SERVER['actions.posts.invalidate'] = false;

        $this->getJson('/restify-api/posts/actions')
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

    public function test_could_perform_action_for_repository()
    {
        $post = $this->mockPosts(
            $this->mockUsers()->first()->id
        );

        $this->post('/restify-api/posts/'.$post->first()->id.'/action?action='.(new PublishPostAction())->uriKey(), [
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals($post->first()->id, PublishPostAction::$applied[0][0]->id);
    }
}
