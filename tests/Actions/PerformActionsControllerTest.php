<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\ActivateAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\DisableProfileAction;
use Binaryk\LaravelRestify\Tests\IntegrationTest;

class PerformActionsControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_perform_action_for_multiple_repositories()
    {
        $post = $this->mockPosts(1, 2);

        $this->post('posts/action?action='.(new PublishPostAction())->uriKey(), [
            'repositories' => [
                $post->first()->id,
                $post->last()->id,
            ],
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        // Repositories are sorted desc by primary key.
        $this->assertEquals(2, PublishPostAction::$applied[0][0]->id);
        $this->assertEquals(1, PublishPostAction::$applied[0][1]->id);
    }

    public function test_cannot_apply_a_show_action_to_index()
    {
        $post = $this->mockPosts(1, 2);

        $_SERVER['actions.posts.invalidate'] = true;
        $_SERVER['actions.posts.publish.onlyOnShow'] = true;

        $this->post('posts/action?action='.(new PublishPostAction())->uriKey(), [
            'repositories' => [
                $post->first()->id,
                $post->last()->id,
            ],
        ])
            ->assertNotFound()
            ->assertJsonStructure([
                'errors',
            ]);
    }

    public function test_show_action_not_need_repositories()
    {
        $users = $this->mockUsers();

        $this->post('users/'.$users->first()->id.'/action?action='.(new ActivateAction)->uriKey())
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(1, ActivateAction::$applied[0]->id);
    }

    public function test_could_perform_standalone_action()
    {
        $this->post('users/action?action='.(new DisableProfileAction())->uriKey())
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals('foo', DisableProfileAction::$applied[0]);
    }
}
