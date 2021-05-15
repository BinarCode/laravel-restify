<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\ActivateAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\DisableProfileAction;
use Binaryk\LaravelRestify\Tests\IntegrationTest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PerformActionControllerTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_perform_action_for_multiple_repositories(): void
    {
        $post = $this->mockPosts(1, 2);

        $this->postJson('posts/action?action='.(new PublishPostAction())->uriKey(), [
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

    public function test_could_perform_action_using_all()
    {
        $this->assertDatabaseCount('posts', 0);

        PostRepository::partialMock()
            ->shouldReceive('actions')
            ->andReturn([
                new class extends Action {
                    public static $uriKey = 'publish';

                    public function handle(Request $request, Collection $collection)
                    {
                        return response()->json([
                            'fromHandle' => $collection->count(),
                        ]);
                    }
                },
            ]);

        $this->postJson('posts/action?action=publish', [
            'repositories' => 'all',
        ])->assertOk()->assertJsonFragment([
            'fromHandle' => 0,
        ]);
    }

    public function test_cannot_apply_a_show_action_to_index(): void
    {
        $_SERVER['actions.posts.publish.onlyOnShow'] = true;

        $this->postJson('posts/action?action='.(new PublishPostAction())->uriKey(), [])
            ->assertNotFound();
    }

    public function test_show_action_not_need_repositories()
    {
        $users = $this->mockUsers();

        $this->postJson('users/'.$users->first()->id.'/action?action='.(new ActivateAction)->uriKey())
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(1, ActivateAction::$applied[0]->id);
    }

    public function test_could_perform_standalone_action()
    {
        $this->postJson('users/action?action='.(new DisableProfileAction())->uriKey())
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals('foo', DisableProfileAction::$applied[0]);
    }
}
