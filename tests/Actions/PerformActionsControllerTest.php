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

        $this->post('/restify-api/posts/action?action=' . (new PublishPostAction())->uriKey(), [
            'repositories' => [
                $post->first()->id,
                $post->last()->id,
            ],
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data'
            ]);

        $this->assertEquals(1, PublishPostAction::$applied[0][0]->id);
        $this->assertEquals(2, PublishPostAction::$applied[0][1]->id);
    }
}
