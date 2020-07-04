<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
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

        $this->post('/restify-api/posts/action?action='.(new PublishPostAction())->uriKey(), [
            'repositories' => [1, 2],
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals($post->first()->id, PublishPostAction::$applied[0][0]->id);
        $this->assertEquals($post->last()->id, PublishPostAction::$applied[0][1]->id);
    }
}
