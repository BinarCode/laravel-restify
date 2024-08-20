<?php

namespace Binaryk\LaravelRestify\Tests\Actions;

use Binaryk\LaravelRestify\Actions\Action;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PostRepository;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishInvokablePostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\Post\PublishPostAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\ActivateAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\DisableProfileAction;
use Binaryk\LaravelRestify\Tests\Fixtures\User\UserRepository;
use Binaryk\LaravelRestify\Tests\IntegrationTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PerformActionControllerTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
    }

    public function test_could_perform_action_for_multiple_repositories(): void
    {
        $post = $this->mockPosts(1, 2);

        $this->postJson(PostRepository::action(PublishPostAction::class), [
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

    public function test_could_perform_invokable_action_for_multiple_repositories(): void
    {
        $post = $this->mockPosts(1, 2);

        $this->postJson(PostRepository::action(PublishInvokablePostAction::class), [
            'repositories' => [
                $post->first()->id,
                $post->last()->id,
            ],
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);
    }

    public function test_could_perform_action_using_all(): void
    {
        $this->assertDatabaseCount('posts', 0);

        PostRepository::partialMock()
            ->shouldReceive('actions')
            ->andReturn([
                new class extends Action
                {
                    public static $uriKey = 'publish';

                    public function handle(Request $request, Collection $collection)
                    {
                        return response()->json([
                            'fromHandle' => $collection->count(),
                        ]);
                    }
                },
            ]);

        $this->postJson(PostRepository::route('actions', query: ['action' => 'publish']), [
            'repositories' => 'all',
        ])->assertOk()->assertJsonFragment([
            'fromHandle' => 0,
        ]);
    }

    public function test_cannot_apply_a_show_action_to_index(): void
    {
        $_SERVER['actions.posts.publish.onlyOnShow'] = true;

        $this
            ->postJson(PostRepository::action(PublishPostAction::class), [])
            ->assertNotFound();
    }

    public function test_show_action_not_need_repositories(): void
    {
        $users = $this->mockUsers();

        $this->postJson(UserRepository::action(ActivateAction::class, $users->first()->id))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals(1, ActivateAction::$applied[0]->id);
    }

    public function test_could_perform_standalone_action(): void
    {
        $this->postJson(UserRepository::action(DisableProfileAction::class))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertEquals('foo', DisableProfileAction::$applied[0]);
    }
}
